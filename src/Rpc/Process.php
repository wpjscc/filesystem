<?php

namespace React\Filesystem\Rpc;

use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use ReactphpX\Rpc\Tcp\TcpClient;

class Process
{
    static ?TcpClient $client = null;
    static string $address = '127.0.0.1:8080';

    public static function setAddress(string $address): void
    {
        static::$address = $address;
    }

    public static function call(string $method, array $arguments = []): PromiseInterface
    {
        if (!static::$client) {
            static::init();
        }
        return static::$client->call($method, $arguments);
    }

    public static function init(): void
    {
        if (static::$client !== null) {
            return;
        }

        $loop = Loop::get();
        $connector = new Connector($loop);
        
        static::$client = new TcpClient(static::$address, $connector);
    }

    public static function close(): void
    {
        if (static::$client) {
            static::$client->close();
            static::$client = null;
        }
    }
}

