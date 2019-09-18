<?php

class GameException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        Debug::Log($code . ': ' . $message, parent::getFile(), 'EXCEPTION');
        parent::__construct($message, $code, $previous);
    }
}