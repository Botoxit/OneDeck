<?php
/**
 * Created by PhpStorm.
 * User: nnicu
 * Date: 14.04.2019
 * Time: 19:34
 */

phpinfo();

$asd = new DataBase();
echo $asd->readTableList();

class DataBase
{
    //Database=localdb;Data Source=127.0.0.1;User Id=azure;Password=6#vWHD_$
    private $servername = "127.0.0.1:53206";//"localhost";
    private $username = "azure";
    private $DBpassword = "6#vWHD_$";
    private $dbName = "localdb";
    private $conn;


    public function __construct()
    {
        date_default_timezone_set('Europe/Bucharest');
        $conn = new mysqli($this->servername, $this->username, $this->DBpassword, $this->dbName);
        if ($conn->connect_error)
            die(json_encode(array('status' => 404, 'message' => $conn->errno . " text:" . $conn->connect_error)));
    }

    public function readTableList()
    {
        $sql = "SELECT * FROM `Lista_Mese`";
        $result = $this->conn->query($sql);
        return $result;
    }
}