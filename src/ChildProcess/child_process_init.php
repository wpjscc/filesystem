<?php

use React\Stream\WritableResourceStream;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../../../vendor/autoload.php';
}

use ReactphpX\TunnelStream\TunnelStream;
use React\Stream\ReadableResourceStream;


$tunnelStream = new TunnelStream(
    new ReadableResourceStream(STDIN), 
    new WritableResourceStream(STDERR), 
true);