<?php
// Basic configuration. Edit values for your environment.
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'book_review_api',

    // JWT config - change secret to a long random string in production
    'jwt_secret' => 'REPLACE_WITH_A_STRONG_SECRET_KEY',
    'jwt_issuer' => 'yourdomain.local', // optional issuer
    'jwt_exp' => 3600, // token lifetime in seconds (1 hour)

    // OTP settings
    'otp_ttl_seconds' => 15 * 60, // 15 minutes

    // Mail (PHPMailer)
    // For Zoho: either SSL on 465 or STARTTLS on 587
    // If using port 465, set encryption => 'ssl'; if 587, set 'tls'
    'mail' => [
        'host' => 'smtp.zoho.com',
        'username' => 'williamsondivine82@zohomail.com',
        'password' => 'Onyinye@56$',
        'port' => 465,
        'encryption' => 'ssl', // 'ssl' for 465, 'tls' for 587
        'from_email' => 'williamsondivine82@zohomail.com', // must match/account on Zoho
        'from_name' => 'Book Review API'
    ],

    // Uploads
    'upload_dir' => __DIR__ . '/../public/uploads',
    'upload_url' => '/public/uploads' // adjust to your webserver setup
];
