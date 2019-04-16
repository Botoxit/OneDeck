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
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->server_name, $this->username, $this->password, $this->database);
        } catch (mysqli_sql_exception $sql_exceptione) {
            die(json_encode(array('status' => $sql_exceptione->getCode(), 'message' => $sql_exceptione->getMessage())));
        }

        return $this->conn;
    }
}