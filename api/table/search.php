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

// get database connection
$conn = DataBase::getConnection();
// get posted data
$post = json_decode(file_get_contents("php://input"));

$search = false;

if (empty($post->id)) $start_id = 0; else $start_id = $post->id;    // paging
if (!empty($post->name)) {
    $name = $post->name;
    $search = true;
} else $name = null;

if (!empty($post->password)) {
    $password = $post->password;
    $search = true;
} else $password = null;

if (!empty($post->game)) {
    $game = $post->game;
    $search = true;
} else $game = null;

if (!empty($post->players_limit)) {
    $players_limit = $post->players_limit;
    $search = true;
} else $players_limit = null;

if (!empty($post->rules)) {
    $rules = $post->rules;
    $search = true;
} else $rules = null;

$table = new Table();
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
        "password" => $row['password'] == '' ? '' : 'X',
        "game" => $row['game'],
        "players_limit" => $row['players_limit'],
        "rules" => json_decode($row['rules'])
    );
    array_push($table_list, $table_item);
}

http_response_code(200);    // set response code - 200 OK
if (count($table_list) > 0)
    die(json_encode(array('status' => 1, 'table' => $table_list)));
die(json_encode(array('status' => 0)));