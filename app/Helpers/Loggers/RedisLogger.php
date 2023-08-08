<?php

namespace App\Helpers\Loggers;

use Illuminate\Support\Facades\Redis;

class RedisLogger
{
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return Redis::set($key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return Redis::get($key);
    }
}