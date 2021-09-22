<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 19:34
 */

require_once 'Debug.php';

error_reporting(E_ALL);
ini_set("display_errors", "On");
if(session_id() == '' || !isset($_SESSION)) {
    // session isn't started
    session_start();
}

class Database
{
    private const HOST = "localhost";
    private const USER = "dinopyco_onedeck";
    private const PASSWORD = "l%f1YZ*oj;z;";
    private const DATABASE = "dinopyco_onedeck";
    private const PORT = 3306;
    private static $connection;

    /**
     * @return mysqli - MySQL connection
     *
     * if already exist a connection with db, return $connection
     * else a new connection is established
     *
     * We set autocommit on false because we want to save changes in db
     * only if all the steps have been completed
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection != null && get_class(self::$connection) == mysqli::class)
            return self::$connection;
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$connection = new mysqli(self::HOST, self::USER, self::PASSWORD, self::DATABASE, self::PORT);
            self::$connection->autocommit(false);
            self::$connection->set_charset("utf8");
            mysqli_report(MYSQLI_REPORT_OFF);
        } catch (mysqli_sql_exception $sql_exception) {
            Debug::Log($sql_exception->getCode() . ':' . $sql_exception->getMessage(), basename(__FILE__), "ERROR");
            die(json_encode(array('status' => 0, 'message' => 'Something is wrong')));
        }
        return self::$connection;
    }
}