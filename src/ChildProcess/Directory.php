<?php

namespace React\Filesystem\ChildProcess;

use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Promise\PromiseInterface;
use function React\Promise\all;

final class Directory implements Node\DirectoryInterface
{
    use StatTrait;

    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(AdapterInterface $filesystem, string $path, string $name)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function ls(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return Process::call(function () use ($path) {
            $nodes = [];
            foreach (scandir($path) as $node) {
                if (in_array($node, ['.', '..'])) {
                    continue;
                }
                $nodes[] = $node;
            }
            return $nodes;
        })->then(function ($nodes) use ($path) {
            $promises = [];
            foreach ($nodes as $node) {
                $promises[] = $this->filesystem->detect($this->path . $this->name . DIRECTORY_SEPARATOR . $node);
            }
            return all($promises);
        });
    }

    public function unlink(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return Process::call(function () use ($path) {
            if (count(scandir($path)) > 2) { // '.' and '..'
                return false;
            }
            return rmdir($path);
        });
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
    }
}

