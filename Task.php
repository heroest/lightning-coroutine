<?php

namespace Lightning\Coroutine;

use Generator;
use Throwable;

class Task
{
    /** @var Generator $coroutine */
    private $coroutine;
    private $taskId;
    private $deferred = null;
    private $isCancelled = false;

    public function __construct($coroutine)
    {
        $this->taskId = mt_rand(1000, 9999);
        $this->coroutine = $coroutine;
        $this->deferred = new Deferred(function() {
            $this->isCancelled = true;
            $this->coroutine = null;
        });
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function set($mixed)
    {
        if ($this->isCancelled) {
            return;
        } elseif ($mixed instanceof Throwable) {
            $this->coroutine->throw($mixed);
        } else {
            $this->coroutine->send($mixed);
        }

        if ($this->isFinished()) {
            $result = $this->get();
            if ($result instanceof Throwable) {
                $this->deferred->reject($result);
            } else {
                $this->deferred->resolve($result);
            }
        }
    }

    public function get()
    {
        if ($this->isCancelled) {
            return;
        } elseif ($this->coroutine->valid()) {
            return $this->coroutine->current();
        } else {
            return $this->coroutine->getReturn();
        }
    }

    public function isFinished()
    {
        return $this->isCancelled or !$this->coroutine->valid();
    }

    public function promise()
    {
        return $this->deferred->promise();
    }
}