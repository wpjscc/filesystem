<?php

namespace React\Filesystem\Rpc;

use React\Filesystem\Node\FileInterface;
use React\Promise\PromiseInterface;

final class File implements FileInterface
{
    use StatTrait;

    private Adapter $adapter;
    private string $path;
    private string $name;

    public function __construct(Adapter $adapter, string $path, string $name)
    {
        $this->adapter = $adapter;
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

    public function getContents(int $offset = 0, ?int $maxlen = null): PromiseInterface
    {
        $path = $this->path . $this->name;
        return $this->adapter->getProcess()->call('file_get_contents', [$path, $offset, $maxlen]);
    }

    public function putContents(string $contents, int $flags = 0): PromiseInterface
    {
        // Making sure we only pass in one flag for security reasons
        if (($flags & \FILE_APPEND) == \FILE_APPEND) {
            $flags = \FILE_APPEND;
        } else {
            $flags = 0;
        }

        $path = $this->path . $this->name;
        return $this->adapter->getProcess()->call('file_put_contents', [$path, $contents, $flags]);
    }

    public function unlink(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return $this->adapter->getProcess()->call('unlink', [$path]);
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

