<?php

namespace App\Helper;

class Timer
{
    private array $startTime;

    public function start($label = 'default'): void
    {
        $this->startTime[$label] = microtime(true);
    }

    public function end($label = 'default')
    {
        if (!isset($this->startTime[$label])) {
            throw new \Exception("Timer with label '$label' not started.");
        }
        $endTime = microtime(true);
        $elapsedTime = $endTime - $this->startTime[$label];
        unset($this->startTime[$label]);
        return $elapsedTime;
    }
}