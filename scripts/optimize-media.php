<?php

declare(strict_types=1);

/**
 * One-off media optimizer for images already in public/uploads.
 *
 *   php scripts/optimize-media.php
 *
 * - Backs up the whole uploads folder to storage/backups/uploads-<timestamp>/ first.
 * - Downsizes anything wider than 2000px, recompresses, and converts opaque
 *   PNGs to JPEG (same rules Uploader::storeImage() now applies to new uploads).
 * - Updates every database column that referenced a renamed file.
 * - Creates the 640px "-thumb" copies that grids/cards load.
 * - Safe to re-run: files that are already optimal are left alone.
 */

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('MAX_UPLOAD_IMAGE_BYTES', 2 * 1024 * 1024);
require ROOT_PATH . '/app/Core/Uploader.php';

use App\Core\Uploader;

$env = [];
foreach (file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] !== '#' && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? '3306', $env['DB_DATABASE']),
    $env['DB_USERNAME'] ?? 'root',
    $env['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$uploads = PUBLIC_PATH . '/uploads';
$backup = ROOT_PATH . '/storage/backups/uploads-' . date('Ymd-His');
@mkdir($backup, 0755, true);

$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->isFile() && preg_match('/\.(jpe?g|png|webp)$/i', $f->getFilename()) && !str_contains($f->getFilename(), '-thumb.')) {
        $files[] = str_replace('\\', '/', $f->getPathname());
    }
}

foreach ($files as $abs) {
    $rel = substr($abs, strlen(str_replace('\\', '/', $uploads)) + 1);
    @mkdir(dirname("$backup/$rel"), 0755, true);
    copy($abs, "$backup/$rel");
}
echo "Backed up " . count($files) . " files to $backup\n";

// String-like columns that may hold an upload path (plain text, HTML, or JSON).
$columns = $pdo->query(
    "SELECT c.TABLE_NAME, c.COLUMN_NAME FROM information_schema.COLUMNS c
       JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
      WHERE c.TABLE_SCHEMA = DATABASE()
        AND c.DATA_TYPE IN ('varchar','char','text','tinytext','mediumtext','longtext','json')
        AND c.EXTRA NOT LIKE '%GENERATED%'"
)->fetchAll(PDO::FETCH_NUM);

$totalBefore = $totalAfter = $changed = $renamed = $dbRows = 0;

foreach ($files as $abs) {
    $rel = substr($abs, strlen(str_replace('\\', '/', $uploads)) + 1);
    $public = '/uploads/' . $rel;
    $r = Uploader::optimizeExisting($public);
    $totalBefore += $r['before'];
    $totalAfter += $r['changed'] ? $r['after'] : $r['before'];

    if (!$r['changed']) {
        continue;
    }
    ++$changed;
    printf("  %-70s %6d KB -> %5d KB%s\n", $rel, $r['before'] / 1024, $r['after'] / 1024, $r['path'] !== $public ? '  (renamed to ' . basename($r['path']) . ')' : '');

    if ($r['path'] !== $public) {
        ++$renamed;
        $old = ltrim($public, '/');
        $new = ltrim($r['path'], '/');
        $pairs = [[$old, $new], [str_replace('/', '\\/', $old), str_replace('/', '\\/', $new)]];
        foreach ($columns as [$table, $col]) {
            foreach ($pairs as [$o, $n]) {
                $stmt = $pdo->prepare("UPDATE `$table` SET `$col` = REPLACE(`$col`, :o, :n) WHERE `$col` LIKE :like");
                $stmt->execute(['o' => $o, 'n' => $n, 'like' => '%' . $o . '%']);
                $dbRows += $stmt->rowCount();
            }
        }
    }
}

// Thumbnails for everything that shows in cards/grids.
$thumbs = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS)) as $f) {
    if (!$f->isFile() || !preg_match('/\.(jpe?g|png|webp)$/i', $f->getFilename()) || str_contains($f->getFilename(), '-thumb.')) {
        continue;
    }
    $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen(str_replace('\\', '/', $uploads)) + 1);
    if (strtok($rel, '/') === 'sliders') {
        $thumbs += count(Uploader::responsiveVariants('/uploads/' . $rel));
        continue;
    }
    if (in_array(strtok($rel, '/'), Uploader::NO_THUMBNAIL_DIRS, true)) {
        continue;
    }
    $public = '/uploads/' . $rel;
    if (Uploader::thumbnail($public) !== $public) {
        ++$thumbs;
    }
}

printf(
    "\nOptimized %d of %d files (%d renamed PNG->JPG, %d database rows updated), %d thumbnails ensured.\nSize: %.1f MB -> %.1f MB (saved %.1f MB)\n",
    $changed, count($files), $renamed, $dbRows, $thumbs,
    $totalBefore / 1048576, $totalAfter / 1048576, ($totalBefore - $totalAfter) / 1048576
);
