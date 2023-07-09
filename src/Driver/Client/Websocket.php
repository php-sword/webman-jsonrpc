<?php

namespace sword\JsonRpc\Driver\Client;

use sword\JsonRpc\Contract\ClientDriverInterface;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;

class Websocket implements ClientDriverInterface
{

    /**
     * @var $client ?AsyncTcpConnection 客户端对象
     */
    private ?AsyncTcpConnection $client;

    /**
     * @var bool 是否已连接
     */
    private bool $isConnected = false;

    /**
     * @var callable $onMessageCallback
     */
    private $onMessageCallback;

    /**
     * @var callable $onCloseCallback
     */
    private $onCloseCallback;

    /**
     * 连接rpc服务端
     * @param string $connect
     * @return bool
     * @throws Throwable
     */
    public function connect(string $connect): bool
    {
        //连接rpc服务端
        $this->client = new AsyncTcpConnection($connect);

        // websocket握手成功后
        $this->client->onWebSocketConnect = function(AsyncTcpConnection $con) {
            $this->isConnected = true;
        };

        // 当收到消息时
        $this->client->onMessage = function(AsyncTcpConnection $con, $data) {
            if(!is_null($this->onMessageCallback)) {
                $callbackFunc = $this->onMessageCallback;
                $callbackFunc($data);
            }
        };

        // 当连接建断开时
        $this->client->onClose = function($connection){
            echo "rpc client: connection closed and try to reconnect\n";
            $this->isConnected = false;

            if(!is_null($this->onCloseCallback)) {
                $callbackFunc = $this->onCloseCallback;
                $callbackFunc($connection);
            }

            // 如果连接断开，1秒后重连
            $connection->reConnect(1);
        };

        // 开始连接
        $this->client->connect();

        return true;
    }

    public function send(string $data): bool
    {
        if(!$this->isConnected) {
            return false;
        }
        return (bool) $this->client->send($data);
    }

    public function onMessage(callable $callback)
    {
        $this->onMessageCallback = $callback;
    }

    public function onClose(callable $callback)
    {
        $this->onCloseCallback = $callback;
    }

    public function close(): bool
    {
        $this->client->close();
        return true;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

}