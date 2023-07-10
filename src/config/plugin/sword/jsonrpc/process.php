<?php

use sword\JsonRpc\Client;
use sword\JsonRpc\Server;

return [
    // 服务端配置
    'server' => [
        'listen' => 'frame://0.0.0.0:8081',
        'handler' => Server::class,
    ],
    // 客户端配置
    'client' => [
        'listen'  => 'frame://0.0.0.0:2207', //跨进程通信端口
        'connect' => 'ws://localhost:8888/ws', //服务端地址 支持协议 http:// websocket:// tcp://
        'handler' => Client::class,
        'reloadable' => false,
    ]
];