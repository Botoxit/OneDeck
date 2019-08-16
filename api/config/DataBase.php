<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 19:34
 */

include_once 'Debug.php';

error_reporting(E_ALL);
ini_set("display_errors", "On");
if(session_id() == '' || !isset($_SESSION)) {
    // session isn't started
    session_start();
}

class DataBase
{
    private const HOST = "127.0.0.1:53320";
    private const USER = "azure";
    private const PASSWORD = "6#vWHD_$";
    private const DATABASE = "localdb";
    private static $conn;

    /**
     * @return mysqli
     */
    public static function getConnection()
    {
        if (self::$conn != null && get_class(self::$conn) == mysqli::class)
            return self::$conn;
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$conn = new mysqli(self::HOST, self::USER, self::PASSWORD, self::DATABASE);
            self::$conn->autocommit(false);
            mysqli_report(MYSQLI_REPORT_OFF);
        } catch (mysqli_sql_exception $sql_exception) {
            Debug::Log($sql_exception->getCode() . ':' . $sql_exception->getMessage(), basename(__FILE__), "ERROR");
            die(json_encode(array('status' => 0, 'message' => 'Something is wrong')));
        }
        return self::$conn;
    }
}