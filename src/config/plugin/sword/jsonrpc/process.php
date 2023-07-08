<?php

use sword\JsonRpc\Client;
use sword\JsonRpc\Server;

return [
    // 服务端配置
    'RpcServer' => [
        'listen' => 'frame://0.0.0.0:8081',
        'handler' => Server::class,
    ],
    // 客户端配置
    'RpcClient' => [
        'connect' => 'tcp://localhost:8081', //服务端地址 支持协议 http:// websocket:// tcp://
        'handler' => Client::class,
    ]
];