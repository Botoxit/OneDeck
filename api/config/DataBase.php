<?php
/**
 * User: Nicu Neculache
 * Date: 14.04.2019
 * Time: 19:34
 */

class DataBase
{
    private $server_name = "127.0.0.1:53206";
    private $username = "azure";
    private $password = "6#vWHD_$";
    private $database = "localdb";
    private $conn;

    public function getConnection()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->server_name, $this->username, $this->password, $this->database);
            $this->conn->autocommit(false);
        } catch (mysqli_sql_exception $sql_exception) {
            die(json_encode(array('status' => $sql_exception->getCode(), 'message' => $sql_exception->getMessage())));
        }
        return $this->conn;
    }
}