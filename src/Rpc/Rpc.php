<?php

namespace React\Filesystem\Rpc;

use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use ReactphpX\Rpc\Tcp\TcpClient;
use ReactphpX\Rpc\ChildProcess\Client as ChildProcessClient;

/**
 * RPC Client 适配器接口
 */
interface RpcClientInterface
{
    public function call(string $method, array $arguments = []): PromiseInterface;
    public function close(): void;
}

/**
 * TCP RPC Client 适配器
 */
class TcpRpcClientAdapter implements RpcClientInterface
{
    private TcpClient $client;

    public function __construct(string $address)
    {
        $loop = Loop::get();
        $connector = new Connector($loop);
        $this->client = new TcpClient($address, $connector);
    }

    public function call(string $method, array $arguments = []): PromiseInterface
    {
        return $this->client->call($method, $arguments);
    }

    public function close(): void
    {
        $this->client->close();
    }
}

/**
 * ChildProcess RPC Client 适配器
 */
class ChildProcessRpcClientAdapter implements RpcClientInterface
{
    private ChildProcessClient $client;

    public function __construct()
    {
        $loop = Loop::get();
        $this->client = new ChildProcessClient(
            Evaluator::class,
            null,
        );
    }

    public function call(string $method, array $arguments = []): PromiseInterface
    {
        return $this->client->call($method, $arguments);
    }

    public function close(): void
    {
        $this->client->close();
    }
}

/**
 * RPC 实例类
 * 每个实例独立管理自己的 RPC 客户端连接
 */
class Rpc
{
    private ?RpcClientInterface $client = null;
    private string $address;
    private bool $useChildProcess;

    public function __construct(string $address = '127.0.0.1:8080', bool $useChildProcess = false)
    {
        $this->address = $address;
        $this->useChildProcess = $useChildProcess;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
        // 如果客户端已初始化，需要重新创建
        if ($this->client !== null) {
            $this->close();
            $this->client = null;
        }
    }

    public function setUseChildProcess(bool $useChildProcess): void
    {
        $this->useChildProcess = $useChildProcess;
        // 如果客户端已初始化，需要重新创建
        if ($this->client !== null) {
            $this->close();
            $this->client = null;
        }
    }

    public function call(string $method, array $arguments = []): PromiseInterface
    {
        if (!$this->client) {
            $this->init();
        }
        return $this->client->call($method, $arguments);
    }

    protected function init(): void
    {
        if ($this->client !== null) {
            return;
        }

        if ($this->useChildProcess) {
            $this->client = new ChildProcessRpcClientAdapter();
        } else {
            $this->client = new TcpRpcClientAdapter($this->address);
        }
    }

    public function close(): void
    {
        if ($this->client) {
            $this->client->close();
            $this->client = null;
        }
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function isUsingChildProcess(): bool
    {
        return $this->useChildProcess;
    }
}
