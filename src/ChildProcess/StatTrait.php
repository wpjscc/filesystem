<?php

namespace React\Filesystem\ChildProcess;

use React\Filesystem\Stat;
use React\Promise\PromiseInterface;

trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        return Process::call(function () use ($path) {
            if (!file_exists($path)) {
                return null;
            }

            // 返回原始数据数组而不是 Stat 对象，因为对象无法序列化
            return ['path' => $path, 'stat' => stat($path)];
        })->then(function ($result) {
            if ($result === null) {
                return null;
            }

            // 在主进程中构造 Stat 对象
            return new Stat($result['path'], $result['stat']);
        });
    }
}

