<?php

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../vendor/autoload.php';
}

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use ReactphpX\Rpc\Tcp\TcpServer;
use ReactphpX\Rpc\AccessLogHandler;
use React\Filesystem\Rpc\Evaluator as FilesystemEvaluator;

// 获取命令行参数
$address = $argv[1] ?? '127.0.0.1:8080';
$enableAccessLog = isset($argv[2]) && $argv[2] === 'true';

[$host, $port] = explode(':', $address);
$port = (int) $port;

$loop = Loop::get();
$socket = new SocketServer($host . ':' . $port, [], $loop);

// 可选：启用访问日志
$accessLog = null;
if ($enableAccessLog) {
    $accessLog = new AccessLogHandler(true); // true = echo to stdout
}

$evaluator = new FilesystemEvaluator();
$server = new TcpServer($evaluator, $socket, $accessLog);

echo "TCP JSON-RPC Server listening on tcp://{$host}:{$port}\n";
$loop->run();

