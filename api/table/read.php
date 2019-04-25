<?php
/**
 * User: Nicu Neculache
 * Date: 16.04.2019
 * Time: 14:26
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include database and object files
include_once '../config/DataBase.php';
include_once '../objects/Table.php';

// instantiate database and product object
$database = new DataBase();
$conn = $database->getConnection();

// initialize object
$table = new Table($conn);

// get posted data
$data = json_decode(file_get_contents("php://input"));
$search = false;

if (empty($data->id)) $start_id = 0; else $start_id = $data->id;
if (!empty($data->name)) {
    $name = $data->name;
    $search = true;
} else $name = null;

if (!empty($data->password)) {
    $password = $data->password;
    $search = true;
} else $password = null;

if (!empty($data->game)) {
    $game = $data->game;
    $search = true;
} else $game = null;

if (!empty($data->players_limit)) {
    $players_limit = $data->players_limit;
    $search = true;
} else $players_limit = null;

if (!empty($data->rules)) {
    $rules = $data->rules;
    $search = true;
} else $rules = null;

if (!$search) {
    $result = $table->readPaging($start_id, 100);
    if (is_string($result))
        die(json_encode(array("status" => -1, "message" => "sql_exception " . $result)));
    if (!$result)
        die(json_encode(array("status" => -1, "message" => "Unable to read database.")));
    $rowCount = $result->num_rows;
} else {
    $result = $table->search($start_id, 100, $name, $password, $game, $players_limit, $rules);
    if (is_string($result))
        die(json_encode(array("status" => -1, "message" => "sql_exception " . $result)));
    if (!$result)
        die(json_encode(array("status" => -1, "message" => "Unable to read database.")));
    $rowCount = $result->num_rows;
}

$table_list = array();
while ($row = $result->fetch_assoc()) {
    $table_item = array(
        "id" => $row['id'],
        "name" => $row['name'],
        "password" => $row['password'] == '' ? '' : 'da',
        "game" => $row['game'],
        "players_limit" => $row['players_limit'],
        "rules" => json_decode($row['rules'])
    );
    array_push($table_list, $table_item);
}
// set response code - 200 OK
http_response_code(200);
if (count($table_list) > 0)
    die(json_encode(array('status' => 1, 'table' => $table_list)));
die(json_encode(array('status' => 0)));