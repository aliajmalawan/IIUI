<?php

declare(strict_types=1);

namespace App\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * A read-through browser over everything Uploader::storeImage() has already
 * saved under public/uploads/ — every existing module (Faculty, Students,
 * Staff, Gallery, Sliders, Homepage, Pages) writes its own path string
 * straight into its own table and never touches a shared table, so there is
 * no database row to be the source of truth here. This scans the filesystem
 * directly rather than introducing a second, disconnected upload system
 * backed by the (currently unused) `media` table — a picker that inserted
 * rows there wouldn't be visible to any of those existing upload fields
 * anyway. Deleting a file here is a plain filesystem operation with no
 * usage-tracking, so it can leave a dangling reference in whichever record
 * pointed at it — the confirm prompt in the view calls this out.
 */
final class MediaLibrary
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** @return array<int,array<string,mixed>> every image file under public/uploads/, newest first. */
    public static function scan(): array
    {
        $root = self::root();
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $extension = strtolower($fileInfo->getExtension());
            if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                continue;
            }

            // Generated copies ("-thumb", "-w640") live next to their original;
            // listing them would show every photo two or three times.
            if (preg_match('/-(?:thumb|w\d+)\.[A-Za-z0-9]+$/', $fileInfo->getFilename())) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($root) + 1));
            $dimensions = @getimagesize($fileInfo->getPathname());

            $files[] = [
                'relative_path' => $relativePath,
                'folder'        => explode('/', $relativePath)[0],
                'url'           => '/uploads/' . $relativePath,
                'size_bytes'    => $fileInfo->getSize(),
                'width'         => $dimensions[0] ?? null,
                'height'        => $dimensions[1] ?? null,
                'modified_at'   => $fileInfo->getMTime(),
            ];
        }

        usort($files, static fn (array $a, array $b) => $b['modified_at'] <=> $a['modified_at']);

        return $files;
    }

    /** @return array<int,string> top-level subfolder names under public/uploads/, for the filter dropdown. */
    public static function folders(): array
    {
        $root = self::root();
        if (!is_dir($root)) {
            return [];
        }

        $folders = [];
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folders[] = basename($dir);
        }
        sort($folders);

        return $folders;
    }

    /** @throws RuntimeException if the path escapes public/uploads/ or doesn't point at a real file. */
    public static function delete(string $relativePath): void
    {
        $root = realpath(self::root());
        $target = realpath(self::root() . '/' . ltrim($relativePath, '/'));

        if ($root === false || $target === false || !str_starts_with($target, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Invalid file path.');
        }
        if (!is_file($target)) {
            throw new RuntimeException('File not found.');
        }

        unlink($target);

        // Remove the generated copies of this image too, so no orphans are left behind.
        $base = preg_replace('/\.[A-Za-z0-9]+$/', '', $target);
        $ext = pathinfo($target, PATHINFO_EXTENSION);
        foreach (array_merge([$base . '-thumb.' . $ext], glob($base . '-w*.' . $ext) ?: []) as $copy) {
            if (is_file($copy)) {
                unlink($copy);
            }
        }
    }

    private static function root(): string
    {
        return PUBLIC_PATH . '/uploads';
    }
}
