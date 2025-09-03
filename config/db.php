<?php

return [
    'mongodb' => [
        'dsn' => getenv('DB_DSN') ?: 'mongodb://localhost:27017',
        'database' => getenv('DB_NAME') ?: 'user_management',
        'options' => [
            'username' => getenv('DB_USER') ?: '',
            'password' => getenv('DB_PASS') ?: '',
            'authSource' => 'admin',
            'retryWrites' => true,
            'w' => 'majority',
        ]
    ]
];