<?php 

require 'vendor/autoload.php';
require 'TaskScheduler.php';
require 'Task.php';

$loop = \React\EventLoop\Factory::create();

$loop->addPeriodicTimer(3, function (){
    echo "other loop continue\r\n";
});

$callback = function () use ($loop) {
    $deferred = new \React\Promise\Deferred();
    $loop->addTimer(5, function () use ($deferred) {
        $deferred->resolve('delay_result');
    });
    echo "before yield\r\n";
    $promise_value = yield $deferred->promise();
    echo "promise_value: {$promise_value}\r\n";
};

$scheduler = new \Lightning\Coroutine\TaskScheduler();
$coroutine = call_user_func($callback);
$scheduler->appendTask($coroutine);

$loop->addPeriodicTimer(1, function () use ($scheduler) {
    if (!$scheduler->isEmpty()) {
        $scheduler->tick();
    }
});

echo "loop started\r\n";
$loop->run();