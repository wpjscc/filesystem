<?php

use React\Stream\WritableResourceStream;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../vendor/autoload.php';
}

use ReactphpX\TunnelStream\TunnelStream;
use React\Stream\ReadableResourceStream;


$tunnelStream = new TunnelStream(
    new ReadableResourceStream(STDIN), 
    new WritableResourceStream(STDERR), 
true);

function getMemoryUsage(): array
{
    return [
        'current_usage_mb' => round(memory_get_usage() / 1024 / 1024, 3),
        'current_usage_real_mb' => round(memory_get_usage(true) / 1024 / 1024, 3),
        'peak_usage_mb' => round(memory_get_peak_usage() / 1024 / 1024, 3),
        'peak_usage_real_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
    ];
}

$startMemoryUsage = getMemoryUsage();

\React\EventLoop\Loop::addPeriodicTimer(1, function () use ($startMemoryUsage) {
    echo json_encode([
        'start_memory_usage' => $startMemoryUsage,
        'current_memory_usage' => getMemoryUsage(),
    ], JSON_PRETTY_PRINT) . "\n";
});