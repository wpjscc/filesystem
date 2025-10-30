<?php

namespace React\Filesystem\Rpc;

/**
 * RPC 方法评估器，实现文件系统操作的 RPC 方法
 */
class Evaluator implements \ReactphpX\Rpc\Evaluator
{
    public function evaluate($method, $arguments)
    {
        return match ($method) {
            'stat' => $this->stat($arguments),
            'file_get_contents' => $this->fileGetContents($arguments),
            'file_put_contents' => $this->filePutContents($arguments),
            'unlink' => $this->unlink($arguments),
            'scandir' => $this->scandir($arguments),
            'mkdir' => $this->mkdir($arguments),
            'rmdir' => $this->rmdir($arguments),
            default => throw new \RuntimeException("Method '{$method}' not found", -32601),
        };
    }

    private function stat(array $arguments): ?array
    {
        $path = $arguments[0] ?? '';
        if (!file_exists($path)) {
            return null;
        }
        return ['path' => $path, 'stat' => stat($path)];
    }

    private function fileGetContents(array $arguments): string
    {
        $path = $arguments[0] ?? '';
        $offset = $arguments[1] ?? 0;
        $maxlen = $arguments[2] ?? null;
        
        if ($maxlen === null && file_exists($path)) {
            $maxlen = (int)stat($path)['size'];
        }
        
        return file_get_contents($path, false, null, $offset, $maxlen);
    }

    private function filePutContents(array $arguments): int
    {
        $path = $arguments[0] ?? '';
        $contents = $arguments[1] ?? '';
        $flags = $arguments[2] ?? 0;
        
        // Making sure we only pass in one flag for security reasons
        if (($flags & \FILE_APPEND) == \FILE_APPEND) {
            $flags = \FILE_APPEND;
        } else {
            $flags = 0;
        }
        
        return file_put_contents($path, $contents, $flags);
    }

    private function unlink(array $arguments): bool
    {
        $path = $arguments[0] ?? '';
        return unlink($path);
    }

    private function scandir(array $arguments): array
    {
        $path = $arguments[0] ?? '';
        $nodes = [];
        foreach (scandir($path) as $node) {
            if (in_array($node, ['.', '..'])) {
                continue;
            }
            $nodes[] = $node;
        }
        return $nodes;
    }

    private function mkdir(array $arguments): bool
    {
        $path = $arguments[0] ?? '';
        mkdir($path, 0777, true);
        return true;
    }

    private function rmdir(array $arguments): bool
    {
        $path = $arguments[0] ?? '';
        if (count(scandir($path)) > 2) { // '.' and '..'
            return false;
        }
        return rmdir($path);
    }
}

