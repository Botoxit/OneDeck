<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 19:34
 */


error_reporting(E_ALL);
ini_set("display_errors", "On");

class DataBase
{
    private const HOST = "127.0.0.1:53257";
    private const USER = "azure";
    private const PASSWORD = "6#vWHD_$";
    private const DATABASE = "localdb";
    private static $conn;

    /**
     * @return mysqli
     */
    public static function getConnection()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        if (get_class(self::$conn) == get_class(mysqli::class))
            return self::$conn;
        try {
            self::$conn = new mysqli(self::HOST, self::USER, self::PASSWORD, self::DATABASE);
            self::$conn->autocommit(false);
        } catch (mysqli_sql_exception $sql_exception) {
            Debug::Log($sql_exception->getCode() . ':' . $sql_exception->getMessage(), __FILE__, "ERROR");
            die(json_encode(array('status' => 0, 'message' => 'Something is wrong')));
        }
        return self::$conn;
    }
}