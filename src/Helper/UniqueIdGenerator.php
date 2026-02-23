<?php

namespace App\Helper;

class UniqueIdGenerator
{

    public function generate(): string
    {
        $timestamp = dechex(floor(microtime(true) * 10000));
        return bin2hex(random_bytes(6)).$timestamp;
    }
}