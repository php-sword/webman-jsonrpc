<?php

namespace sword\JsonRpc;

use sword\JsonRpc\Contract\ClientDriverInterface;
use sword\JsonRpc\Driver\Client\Websocket;
use sword\JsonRpc\Exception\JsonRpcException;
use Throwable;

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

        $config = config('plugin.sword.jsonrpc.process.client');

        $connect = $config['connect'];

        //获取协议
        $protocol = substr($connect, 0, strpos($connect, '://'));

        /**
         * @var ClientDriverInterface $clientObj 客户端对象
         */
        $clientObj = null;

        // 根据协议创建客户端对象
        if($protocol == 'ws'){
            $clientObj = new Websocket();
        }else{
            throw new JsonRpcException('协议暂不支持:'. $protocol . '://');
        }

        $clientObj->onMessage(function ($data) {
            //解析请求数据
            $response = json_decode($data, true);

            //获取请求id
            $id = $response['id']??null;
            if (is_null($id)) {
                echo "rpc client: response id is null\n{$data}\n";
                return;
            }

            //发布响应
            ChannelClient::publish('rpcResponse:'. $id, $data);
        });

        $clientObj->connect($connect);

        //订阅通道
        ChannelClient::connect('127.0.0.1', 2207);

        // 订阅rpc客户端请求的通道
        ChannelClient::on('rpcRequest', function($event_data) use ($clientObj){
            // 发送数据到 rpc 服务端
            $clientObj->send($event_data);
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

        ChannelClient::connect('127.0.0.1', 2207);

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
        ChannelClient::publish('rpcRequest', json_encode($request, JSON_UNESCAPED_UNICODE));

        // 等待 rpc 服务端返回数据 ，并设置超时时间
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