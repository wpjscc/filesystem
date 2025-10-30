<?php

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use function React\Async\async;
use function React\Async\delay;
use React\EventLoop\Loop;

Loop::addPeriodicTimer(1, function () {
    echo 'current_memory_usage: ' . round(memory_get_usage() / 1024 / 1024, 3) . "\n";
});

$filesystem = Factory::createChildProcess();
// memory leak when using Factory::create()
// $filesystem = Factory::create();

function getMemoryUsage(): array
{
    return [
        'current_usage_mb' => round(memory_get_usage() / 1024 / 1024, 3),
        'current_usage_real_mb' => round(memory_get_usage(true) / 1024 / 1024, 3),
        'peak_usage_mb' => round(memory_get_peak_usage() / 1024 / 1024, 3),
        'peak_usage_real_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
    ];
}

$file = $filesystem->file(__DIR__ . '/test.txt');
async(function () use ($file) {
    $startMemoryUsage = getMemoryUsage();
    while (true) {
        delay(0.001);
        $file->putContents(
            json_encode([
                'start_memory_usage' => $startMemoryUsage,
                'current_memory_usage' => getMemoryUsage(),
                'memory_limit' => ini_get('memory_limit'),
            ], JSON_PRETTY_PRINT),
        )->then(function () {
            // echo 'file written' . "\n";
        });
    }
})();
