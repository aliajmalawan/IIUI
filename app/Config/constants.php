<?php

declare(strict_types=1);

/**
 * True, environment-independent constants only. Anything that varies by
 * deployment (URLs, credentials, feature toggles) belongs in a Config/*.php
 * file and is read through config(), never defined here.
 *
 * Required in the bootstrap chain (public/index.php) right after the path
 * constants — see docs/01-architecture.md §4.
 */

define('APP_VERSION', '0.1.0-dev');

define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE', 100);

define('MAX_UPLOAD_IMAGE_BYTES', 2 * 1024 * 1024);      // 2 MB — student/staff photos
define('MAX_UPLOAD_DOCUMENT_BYTES', 5 * 1024 * 1024);   // 5 MB — B-Forms, CNIC scans, PDFs
define('MAX_UPLOAD_HERO_IMAGE_BYTES', 15 * 1024 * 1024); // 15 MB — full-width hero/banner photos, higher-res than avatar-style uploads
