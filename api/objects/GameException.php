<?php

class GameException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        Debug::Log($code . ': ' . $message, parent::getFile(), 'EXCEPTION');
        parent::__construct($message, $code, $previous);
    }

    private static $messages = array(
        "Unknown error",                            // 0
        "Unable to read player",                    // 1
        "Unable to read macao game data",           // 2
        "Unable to update macao game data",         // 3
        "Unable to commit",                         // 4
        "Unable to read ready player",              // 5
        "Unable to update player",                  // 6
        "Unable to read all players from table",    // 7
        "Bad request, data is missing.",            // 8
        "It's not your cards! YOU ARE A CHEATER!",  // 9
        "Unable to create player",                  // 10
        "Unable to create table",                   // 11
        "Unable to update table",                   // 12
        "Unable to delete table",                   // 13
        "Unable to delete player",                  // 14
        "Unable to read player",                    // 15
        "Unable to read page",                      // 16
        "Unable to change game host",               // 17
        "Unable to search tables",                  // 18
        "Not found",                                // 19
        "Table is full",                            // 20
        "id_player is empty",                       // 21
        "Update your application",                  // 22
    );

    public static function exitMessage(int $code)
    {
        if ($code < count(self::$messages))
            die(json_encode(array("status" => -$code, "message" => self::$messages[$code])));
        die(json_encode(array("status" => -$code, "message" => self::$messages[0])));
    }
}