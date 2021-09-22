<?php

class Debug
{
    /**
     * @param string $message
     * @param string $file
     * @param string $level
     *
     * Write in file the received message
     */
    public static function Log($message = "Unknown error message", $file = "", $level = "WARNING")
    {
        $date = date("Y-m-d H:i:s");
        $message = "[$date][$file][$level] " . trim($message) . PHP_EOL;
        $log_file = dirname(__DIR__) . '/log/' . date("Y-m-d") . '.log';
        error_log($message, 3, $log_file);
    }
}