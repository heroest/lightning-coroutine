<?php

namespace Lightning\Coroutine;

use SplQueue;
use Generator;
use Lightning\Coroutine\Task;

class TaskScheduler
{
    private $taskQueue;

    public function __construct()
    {
        $this->taskQueue = new SplQueue();
    }

    public function execute(callable $callback)
    {
        $result = call_user_func($callback);
        if ($result instanceof Generator) {
            $this->taskQueue->enqueue(new Task($result));
        } else {
            return $result;
        }
    }

    public function tick()
    {
        if ($this->taskQueue->isEmpty()) {
            return;
        }

        $task = $this->taskQueue->dequeue();
        if ($task->isFinished()) {
            return;
        } else {
            $yielded = $task->get();
            if ($yielded instanceof \React\Promise\PromiseInterface) {
                $yielded->then(function ($value) use ($task) {
                    $task->set($value);
                    $this->taskQueue->enqueue($task);
                }, function ($error) use ($task) {
                    $task->throw($error);
                    $this->taskQueue->enqueue($task);
                });
            } elseif ($yielded instanceof Generator) {
                $new_task = new Task($yielded);
                $this->taskQueue->enqueue($new_task);
                $new_task->promise()->then(function ($value) use ($task) {
                    $task->set($value);
                    $this->taskQueue->enqueue($task);
                }, function ($error) use ($task) {
                    $task->throw($error);
                    $this->taskQueue->enqueue($task);
                });
            }
        }
    }

    public function isEmpty()
    {
        return $this->taskQueue->isEmpty();
    }
}