<?php

namespace React\Filesystem\Node;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;

final class Unknown implements NodeInterface
{
    private string $path;
    private string $name;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return resolve(null);
    }

    public function unlink(): PromiseInterface
    {
        return resolve(false);
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

