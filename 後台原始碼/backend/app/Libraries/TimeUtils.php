<?php

namespace App\Libraries;

class TimeUtils
{
    protected static $now = null;
    protected static $time = null;
    protected static $formatter = "Y-m-d H:i:s";

    public static function getTime()
    {
        if (!isset(self::$now)) {
            self::getNow();
        }
        return self::$time;
    }

    public static function getFormatter()
    {
        return self::$formatter;
    }

    public static function getNow()
    {

        if (!isset(self::$now)) {
            // 讓整個 app 每次 request 都使用相同的時間點
            self::$time = time();
            self::$now = date(self::$formatter, self::$time);
        }
        return self::$now;
    }

}
