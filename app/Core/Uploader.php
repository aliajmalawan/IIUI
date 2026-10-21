<?php

declare(strict_types=1);

namespace App\Core;

use finfo;
use RuntimeException;

/**
 * Validated image upload — the seven-point checklist from docs/05-security.md
 * §5: extension allowlist, real MIME sniff (never trust $_FILES['type']),
 * size cap, dimension check, re-encode via GD (destroys any polyglot payload
 * appended to the file), random filename, defense-in-depth PHP-execution
 * deny in the destination folder's .htaccess.
 *
 * Images stored through this class are PUBLIC by design (site logo, favicon,
 * gallery covers) and land under public/uploads/ so a plain <img src> works.
 * Privacy-sensitive documents (CNIC, B-Form) are a different concern — those
 * belong outside the webroot, served only through an authorization-checking
 * controller, and get their own storage path when that module is built.
 */
final class Uploader
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Longest edge kept on disk. 2000px covers a full-width hero on a 1440p screen; phone photos are often 4000px+. */
    private const MAX_EDGE = 2000;
    private const JPEG_QUALITY = 84;
    public const NO_THUMBNAIL_DIRS = ['branding', 'sliders'];
    private const WEBP_QUALITY = 82;

    /** docs/05-security.md §5 point 1 — documents allowlist (denylists are rejected outright). */
    private const ALLOWED_DOC_EXTENSIONS = ['pdf', 'docx', 'xlsx'];

    /**
     * DOCX/XLSX are ZIP containers; a correctly-authored one is usually
     * sniffed as its OOXML mime by finfo, but some environments only see the
     * outer ZIP structure and report 'application/zip'. Both are accepted
     * per extension — the extension allowlist plus this bounded mime set is
     * still far stricter than trusting $_FILES['type'].
     */
    private const ALLOWED_DOC_MIME_TYPES = [
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file one entry from $_FILES
     * @return string public path, e.g. "/uploads/branding/ab12cd34.jpg"
     */
    public static function storeImage(array $file, string $subdirectory, int $maxBytes = MAX_UPLOAD_IMAGE_BYTES): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($error));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('The upload could not be verified.');
        }

        if ((int) $file['size'] > $maxBytes) {
            $mb = round($maxBytes / 1024 / 1024, 1);

            throw new RuntimeException("The image is too large (max {$mb} MB).");
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };

        if ($image === false) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        if ($mime === 'image/jpeg') {
            $image = self::applyExifOrientation($image, $file['tmp_name']);
        }
        $image = self::fitWithin($image, self::MAX_EDGE);

        $subdirectory = trim($subdirectory, '/');
        $destDir = PUBLIC_PATH . '/uploads/' . $subdirectory;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Transparent PNGs (logos, cut-outs) stay PNG. An opaque PNG is almost
        // always a screenshot/photo — re-saved as JPEG it is ~10x smaller with
        // no visible loss, which is what keeps hero images fast.
        $savedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        if ($savedExtension === 'png' && !self::hasTransparency($image)) {
            $savedExtension = 'jpg';
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $savedExtension;
        $destPath = $destDir . '/' . $filename;

        // The re-encode below is also what strips a payload smuggled past the
        // MIME/extension checks above.
        if ($savedExtension === 'jpg') {
            imageinterlace($image, true);
        }
        $saved = match ($savedExtension) {
            'png'   => imagepng($image, $destPath, 7),
            'webp'  => imagewebp($image, $destPath, self::WEBP_QUALITY),
            default => imagejpeg($image, $destPath, self::JPEG_QUALITY),
        };
        imagedestroy($image);

        if (!$saved) {
            throw new RuntimeException('Failed to save the uploaded image.');
        }

        $publicPath = '/uploads/' . $subdirectory . '/' . $filename;

        // Cards and grids load a 640px copy; logos and full-width heroes are shown
        // at their real size, so a thumbnail would only be wasted disk.
        if (!in_array(strtok($subdirectory, '/'), self::NO_THUMBNAIL_DIRS, true)) {
            self::thumbnail($publicPath);
        }
        if (strtok($subdirectory, '/') === 'sliders') {
            self::responsiveVariants($publicPath);
        }

        return $publicPath;
    }

    /**
     * Applies the same size/format rules as storeImage() to a file that is
     * already on disk (used by scripts/optimize-media.php). Rewrites the file
     * only when it actually gets smaller or must be resized, so running it
     * twice never degrades quality a second time.
     *
     * @return array{path:string,before:int,after:int,changed:bool} $path differs from the input when a
     *         fully opaque PNG was converted to JPEG (caller must update references).
     */
    public static function optimizeExisting(string $publicPath): array
    {
        $source = PUBLIC_PATH . '/' . ltrim($publicPath, '/');
        $before = is_file($source) ? (int) filesize($source) : 0;
        $unchanged = ['path' => $publicPath, 'before' => $before, 'after' => $before, 'changed' => false];

        $info = $before > 0 ? @getimagesize($source) : false;
        if ($info === false || !in_array($info['mime'], self::ALLOWED_MIME_TYPES, true)) {
            return $unchanged;
        }

        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            default      => false,
        };
        if ($image === false) {
            return $unchanged;
        }

        $resized = max($info[0], $info[1]) > self::MAX_EDGE;
        $image = self::fitWithin($image, self::MAX_EDGE);

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $newExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        if ($newExtension === 'png' && !self::hasTransparency($image)) {
            $newExtension = 'jpg';
        }

        $tmp = $source . '.opt';
        if ($newExtension === 'jpg') {
            imageinterlace($image, true);
        }
        $ok = match ($newExtension) {
            'png'   => imagepng($image, $tmp, 7),
            'webp'  => imagewebp($image, $tmp, self::WEBP_QUALITY),
            default => imagejpeg($image, $tmp, self::JPEG_QUALITY),
        };
        imagedestroy($image);
        if (!$ok || !is_file($tmp)) {
            @unlink($tmp);

            return $unchanged;
        }

        $after = (int) filesize($tmp);
        $formatChanged = $newExtension !== ($extension === 'jpeg' ? 'jpg' : $extension);
        if (!$resized && !$formatChanged && $after >= $before * 0.9) {
            @unlink($tmp);

            return $unchanged;
        }

        $newPublic = $formatChanged ? preg_replace('/\.[A-Za-z0-9]+$/', '.' . $newExtension, $publicPath) : $publicPath;
        $dest = PUBLIC_PATH . '/' . ltrim($newPublic, '/');
        rename($tmp, $dest);
        if ($formatChanged && $dest !== $source) {
            @unlink($source);
        }

        return ['path' => $newPublic, 'before' => $before, 'after' => $after, 'changed' => true];
    }

    /**
     * Small copy of an already-stored image, saved next to it as
     * "<name>-thumb.<ext>". Grids and cards load this instead of the full
     * photo. Returns the public path (or the original path if the source is
     * missing / already small enough to serve as-is).
     */
    public static function thumbnail(string $publicPath, int $width = 640): string
    {
        return self::variant($publicPath, $width, '-thumb', 78);
    }

    /**
     * Width variants for full-bleed images ("<name>-w640.<ext>", "-w1280"),
     * so a phone downloads a phone-sized hero instead of the 2000px original.
     * Only widths smaller than the source are created.
     *
     * @param int[] $widths
     * @return array<int,string> width => public path, for the variants that exist
     */
    public static function responsiveVariants(string $publicPath, array $widths = [640, 1280]): array
    {
        $out = [];
        foreach ($widths as $w) {
            $path = self::variant($publicPath, $w, '-w' . $w, 80);
            if ($path !== $publicPath) {
                $out[$w] = $path;
            }
        }

        return $out;
    }

    private static function variant(string $publicPath, int $width, string $suffix, int $quality): string
    {
        $source = PUBLIC_PATH . '/' . ltrim($publicPath, '/');
        if (!is_file($source) || preg_match('/-(?:thumb|w\d+)\.[A-Za-z0-9]+$/', $publicPath)) {
            return $publicPath;
        }
        $info = @getimagesize($source);
        if ($info === false || $info[0] <= $width) {
            return $publicPath;
        }

        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            default      => false,
        };
        if ($image === false) {
            return $publicPath;
        }

        $image = self::fitWithin($image, $width);
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $variantPublic = preg_replace('/\.' . preg_quote($extension, '/') . '$/', $suffix . '.' . $extension, $publicPath);
        $dest = PUBLIC_PATH . '/' . ltrim($variantPublic, '/');
        $ok = match ($extension) {
            'png'   => imagepng($image, $dest, 7),
            'webp'  => imagewebp($image, $dest, $quality),
            default => imagejpeg($image, $dest, $quality),
        };
        imagedestroy($image);

        return $ok ? $variantPublic : $publicPath;
    }
    /** Phone cameras store rotation in EXIF instead of rotating the pixels. */
    private static function applyExifOrientation(\GdImage $image, string $file): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($file);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($image);

                return $rotated;
            }
        }

        return $image;
    }

    /** Downscales so the longest edge is at most $max px; never enlarges. */
    private static function fitWithin(\GdImage $image, int $max): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if (max($w, $h) <= $max) {
            return $image;
        }

        $ratio = $max / max($w, $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));
        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($image);

        return $out;
    }

    /** True if any sampled pixel is not fully opaque. */
    private static function hasTransparency(\GdImage $image): bool
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $step = max(1, (int) floor(max($w, $h) / 200));
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                if ((imagecolorat($image, $x, $y) >> 24) & 0x7F) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validated document upload (docs/05-security.md §5): extension
     * allowlist, real MIME sniff, size cap, random filename — same backbone
     * as storeImage(), minus the image-only re-encode step (there is no
     * equivalent transform for a PDF/DOCX/XLSX that would strip a payload).
     * That is exactly why documents never land under public/: they are
     * saved under storage/app/uploads/ (outside the webroot, already denied
     * by storage/.htaccess) and only ever reach a browser through a
     * controller that streams the bytes with a forced Content-Disposition —
     * never a direct, browser-rendered URL.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file one entry from $_FILES
     * @return array{path:string,original_name:string,size:int,mime:string} $path is relative to storage/app/uploads/
     */
    public static function storeDocument(array $file, string $subdirectory, int $maxBytes = MAX_UPLOAD_DOCUMENT_BYTES): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($error));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('The upload could not be verified.');
        }

        $size = (int) $file['size'];
        if ($size > $maxBytes) {
            $mb = round($maxBytes / 1024 / 1024, 1);

            throw new RuntimeException("The file is too large (max {$mb} MB).");
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_DOC_EXTENSIONS, true)) {
            throw new RuntimeException('Only PDF, DOCX, and XLSX files are allowed.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_DOC_MIME_TYPES[$extension], true)) {
            throw new RuntimeException('The uploaded file does not match its extension.');
        }

        $subdirectory = trim($subdirectory, '/');
        $destDir = STORAGE_PATH . '/app/uploads/' . $subdirectory;
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destPath = $destDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Failed to save the uploaded file.');
        }

        return [
            'path'          => $subdirectory . '/' . $filename,
            'original_name' => (string) $file['name'],
            'size'          => $size,
            'mime'          => $mime,
        ];
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image exceeds the maximum upload size.',
            UPLOAD_ERR_PARTIAL => 'The image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'The upload failed. Please try again.',
        };
    }
}
