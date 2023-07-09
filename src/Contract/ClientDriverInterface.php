<?php declare (strict_types=1);

namespace sword\JsonRpc\Contract;

/**
 * 客户端接口
 */
interface ClientDriverInterface
{

    public function connect(string $connect): bool;

    public function send(string $data): bool;

    public function close(): bool;

    public function onMessage(callable $callback);

    public function onClose(callable $callback);

    public function isConnected(): bool;

}
