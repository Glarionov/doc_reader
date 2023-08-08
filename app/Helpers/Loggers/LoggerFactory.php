<?php

namespace App\Helpers\Loggers;

use Exception;

class LoggerFactory
{
    /**
     * @param $loggerType
     * @return RedisLogger
     * @throws Exception
     */
    public static function getLogger($loggerType = 'redis')
    {
        switch ($loggerType) {
            case 'redis':
                return new RedisLogger();
            default:
                throw new Exception("Unknown logger type - $loggerType");
        }
    }
}