<?php

namespace sword\JsonRpc;

use sword\JsonRpc\Exception\JsonRpcException;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;

class Client extends \Channel\Server
{

    public function __construct()
    {
    }

    /**
     * @param $worker
     * @return void
     * @throws Throwable
     */
    public function onWorkerStart($worker)
    {
        $this->_worker = $worker;
        $worker->channels = [];

        $config = config('plugin.sword.jsonrpc.app.RpcClient');

        $connect = $config['connect'];

        //获取协议
        $protocol = substr($connect, 0, strpos($connect, '://'));

        if($protocol == 'websocket'){
            //连接rpc服务端
            $con = new AsyncTcpConnection($connect);

            // websocket握手成功后
            $con->onWebSocketConnect = function(AsyncTcpConnection $con) {
            };

            // 当收到消息时
            $con->onMessage = function(AsyncTcpConnection $con, $data) {
                echo $data;
            };

            $con->connect();
        }else{
            throw new JsonRpcException('协议不支持');
        }

        //订阅通道
        ChannelClient::connect('127.0.0.1', 2207);

        // 订阅rpc客户端请求的通道
        ChannelClient::on('rpc_request', function($event_data) {
            //解析请求数据
//            $request = json_decode($event_data, true);
            var_dump($event_data);
        });

    }

    /**
     * 发起Rpc请求
     * @param string $method 调用方法
     * @param mixed $params 调用参数
     * @param null $id 请求id
     * @param int $timeout 超时时间(秒)
     * @return void
     * @throws JsonRpcException
     */
    public static function request(string $method, $params = [], $id = null, int $timeout = 5)
    {
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        ];

        $response = null;

        if(!is_null($id)){
            $request['id'] = $id;

            //订阅响应
            ChannelClient::on('rpcResponse:'. $request['id'], function($event_data) use (&$response, $request) {
                //储存响应数据
                $response = $event_data;

                //取消订阅
                ChannelClient::unsubscribe('rpcResponse:'. $request['id']);
            });
        }

        // 发送数据到 rpc 服务端
        ChannelClient::publish('rpc_request', $request);

        // 等待 rpc 服务端返回数据 ，并设置超时时间为 5 秒
        $start_time = time();
        while (is_null($response) && time() - $start_time < $timeout) {
            usleep(20);
        }

        if (is_null($response)) {
            throw new JsonRpcException('rpc服务端响应超时');
        }

        return $response;
    }

}