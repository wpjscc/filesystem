<?php

namespace React\Filesystem\Rpc;

use React\Filesystem\Stat;
use React\Promise\PromiseInterface;

trait StatTrait
{
    protected function internalStat(string $path): PromiseInterface
    {
        return Process::call('stat', [$path])->then(function ($result) {
            if ($result === null) {
                return null;
            }

            // 在主进程中构造 Stat 对象
            return new Stat($result['path'], $result['stat']);
        });
    }
}

