<?php
return  [
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => 123456,
        'database' => 0,
    ],
    'consumer' => [
        'interval'     => 1,
        'max_retries'  => 3,
        'retry_delay'  => 5,
    ],
    'producer' => [
        'default_delay' => 0,
    ],
];
