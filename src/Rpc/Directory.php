<?php

namespace React\Filesystem\Rpc;

use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Promise\PromiseInterface;
use function React\Promise\all;

final class Directory implements Node\DirectoryInterface
{
    use StatTrait;

    private AdapterInterface $filesystem;
    private Adapter $adapter;
    private string $path;
    private string $name;

    public function __construct(AdapterInterface $filesystem, string $path, string $name)
    {
        $this->filesystem = $filesystem;
        $this->adapter = $filesystem instanceof Adapter ? $filesystem : throw new \RuntimeException('Invalid adapter type');
        $this->path = $path;
        $this->name = $name;
    }

    protected function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function ls(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return $this->adapter->getProcess()->call('scandir', [$path])->then(function ($nodes) use ($path) {
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
        return $this->adapter->getProcess()->call('rmdir', [$path]);
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

