<?php

return [
    'default' => [
        'url' => env('MONGODB_URL',''),
        'username' => env('MONGODB_USERNAME', ''),
        'password' => env('MONGODB_PASSWORD', ''),
        'host' => env('MONGODB_HOST', '127.0.0.1'),
        'port' => env('MONGODB_PORT', 27017),
        'db' => env('MONGODB_DB', 'test'),
        'authMechanism' => 'SCRAM-SHA-256',
        //设置复制集,没有不设置
        'replica' => 'rs0',
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 100,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float)env('MONGODB_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            //维护数据库每张表的自增id集合名称
            'id_collector' => env('MONGODB_ID_COLLECTOR'),
        ],
    ],
];