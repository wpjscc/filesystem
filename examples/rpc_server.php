<?php

/**
 * RPC 文件系统服务器示例
 * 
 * 此脚本启动一个 TCP JSON-RPC 服务器，用于处理远程文件系统操作请求
 * 
 * 使用方法:
 *   php examples/rpc_server.php [address] [enable_log]
 * 
 * 参数:
 *   address: 服务器监听地址，格式为 host:port，默认为 127.0.0.1:8080
 *   enable_log: 是否启用访问日志，true/false，默认为 false
 * 
 * 示例:
 *   php examples/rpc_server.php 127.0.0.1:8080 true
 */

require __DIR__ . '/../vendor/autoload.php';

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use ReactphpX\Rpc\Tcp\TcpServer;
use ReactphpX\Rpc\AccessLogHandler;
use React\Filesystem\Rpc\Evaluator as FilesystemEvaluator;

// 获取命令行参数
$address = $argv[1] ?? '127.0.0.1:8080';
$enableAccessLog = isset($argv[2]) && ($argv[2] === 'true' || $argv[2] === '1');

// 解析地址
if (!str_contains($address, ':')) {
    echo "错误: 地址格式不正确，应为 host:port (例如: 127.0.0.1:8080)\n";
    exit(1);
}

[$host, $port] = explode(':', $address);
$port = (int) $port;

if ($port <= 0 || $port > 65535) {
    echo "错误: 端口号必须在 1-65535 之间\n";
    exit(1);
}

function getMemoryUsage(): array
{
    return [
        'date' => date('Y-m-d H:i:s'),
        'current_usage_mb' => round(memory_get_usage() / 1024 / 1024, 3),
        'current_usage_real_mb' => round(memory_get_usage(true) / 1024 / 1024, 3),
        'peak_usage_mb' => round(memory_get_peak_usage() / 1024 / 1024, 3),
        'peak_usage_real_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
    ];
}

try {
    $loop = Loop::get();
    $socket = new SocketServer($host . ':' . $port, [], $loop);

    // 可选：启用访问日志
    $accessLog = null;
    if ($enableAccessLog) {
        $accessLog = new AccessLogHandler(true); // true = echo to stdout
    }

    $evaluator = new FilesystemEvaluator();
    $server = new TcpServer($evaluator, $socket, $accessLog);

    // 启动内存监控
    $startMemoryUsage = getMemoryUsage();
    Loop::addPeriodicTimer(1, function () use ($startMemoryUsage) {
        $current = getMemoryUsage();
        echo json_encode([
            'start_memory_usage' => $startMemoryUsage,
            'current_memory_usage' => $current,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    });

    echo "========================================\n";
    echo "TCP JSON-RPC 文件系统服务器\n";
    echo "========================================\n";
    echo "监听地址: tcp://{$host}:{$port}\n";
    echo "访问日志: " . ($enableAccessLog ? '启用' : '禁用') . "\n";
    echo "内存监控: 启用 (每秒更新)\n";
    echo "初始内存: " . json_encode($startMemoryUsage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "========================================\n";
    echo "按 Ctrl+C 停止服务器\n";
    echo "========================================\n\n";

    $loop->run();
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

