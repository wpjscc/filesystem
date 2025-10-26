<?php

require __DIR__ . '/../vendor/autoload.php';

use React\Filesystem\Factory;
use React\EventLoop\Loop;

// 创建子进程适配器 - 将阻塞操作下放到子进程执行
$filesystem = Factory::createChildProcess();

// 读取文件内容
$filesystem->file(__FILE__)->getContents()->then(function ($contents) {
    echo "文件内容长度: " . strlen($contents) . " 字节\n";
    echo "前100个字符: " . substr($contents, 0, 100) . "...\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 列出目录
$filesystem->directory(__DIR__)->ls()->then(function ($nodes) {
    echo "\n目录列表:\n";
    foreach ($nodes as $node) {
        echo "- " . $node->name() . " (" . get_class($node) . ")\n";
    }
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 创建新文件
$filesystem->file(__DIR__ . '/test.txt')->putContents('Hello from ChildProcess!')->then(function ($bytesWritten) {
    echo "\n写入了 $bytesWritten 字节\n";
    
    // 读取刚创建的文件
    global $filesystem;
    return $filesystem->file(__DIR__ . '/test.txt')->getContents();
})->then(function ($contents) {
    echo "读取内容: $contents\n";
    
    // 删除测试文件
    global $filesystem;
    return $filesystem->file(__DIR__ . '/test.txt')->unlink();
})->then(function ($result) {
    echo "文件已删除: " . ($result ? 'true' : 'false') . "\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

Loop::run();

