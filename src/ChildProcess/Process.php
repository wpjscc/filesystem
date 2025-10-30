<?php

namespace React\Filesystem\ChildProcess;

use React\ChildProcess\Process as ReactProcess;
use React\Promise\Deferred;
use ReactphpX\TunnelStream\TunnelStream;
use React\Promise\Promise;

class Process
{
    static ?TunnelStream $tunnelStream = null;
    static ?ReactProcess $process = null;


    public static function call(callable $callable)
    {
        if (!static::$tunnelStream) {
            static::init();
        }
        return static::streamToPromise(static::$tunnelStream->run($callable));
    }

    protected static function streamToPromise($stream)
    {
        $deferred = new Deferred(function () use ($stream) {
            $stream->close();
        });

        $data = null;
        $stream->on('data', function ($buffer) use (&$data) {
            $data = $buffer;
        });

        $stream->on('close', function () use ($deferred, &$data) {
            $deferred->resolve($data);
            $data = null;
        });

        $stream->on('error', function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    public static function init()
    {
        if (static::$tunnelStream !== null) {
            return;
        }
        static::$process = new ReactProcess(sprintf(
            'exec php %s/child_process_init.php',
            __DIR__
        ));
        static::$process->start();

        static::$process->stdout->on('data', function ($data)  {
            echo "[STDOUT] \n" . $data;
        });

        // $process->stderr->on('data', function ($data) {
        //     echo "[STDERR] \n" . $data;
        // });


        // $process->on('exit', function ($exitCode, $termSignal) {
        //     echo "[EXIT] \n" . $exitCode . " " . $termSignal;
        // });

        static::$tunnelStream = new TunnelStream(static::$process->stderr, static::$process->stdin);;

    }

    public static function close()
    {
        if (static::$process) {
            static::$process->close();
            static::$process = null;
        }
    }
}