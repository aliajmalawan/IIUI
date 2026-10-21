<?php

declare(strict_types=1);

// 'log' writes emails to storage/logs/mail-*.log for local development.
// Swap to 'smtp' + PHPMailer once real delivery is needed — see app/Core/Mailer.php.
return [
    'driver'       => env('MAIL_DRIVER', 'log'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@iiuimardan.edu.pk'),
    'from_name'    => env('MAIL_FROM_NAME', 'IIUI Mardan Campus'),
];
