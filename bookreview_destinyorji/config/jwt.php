<?php

return [
    // 🔐 Secret key for signing the JWT
    'secret' => 'my_super_secret_jwt_key', // 🔒 Replace this with a long, random, and secure key (store in .env or config)

    // ⏳ Token expiration in seconds (1 hour)
    'expiration' => 3600,

    // 👤 Who issued the token (optional but recommended)
    'issuer' => 'BookReviewAPI',

    // 👥 Intended audience of the token (optional)
    'audience' => 'BookReviewUsers'
];
