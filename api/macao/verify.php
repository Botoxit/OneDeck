<?php
/**
 * User: Nicu Neculache
 * Date: 23.04.2019
 * Time: 16:21
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// include database and object files
include_once '../config/DataBase.php';
include_once '../objects/Macao.php';
include_once '../objects/Player.php';

// instantiate database and table object
$database = new Database();
$conn = $database->getConnection();

$macao = new Macao($conn);
$player = new Player($conn);

// get posted data
$data = json_decode(file_get_contents("php://input"));

// make sure data is not empty
if (!empty($data->cards)) {

    if (count($data->cards) > 1 && !$_SESSION['deck'])
        die(json_encode(array('status' => 0, 'message' => "Decks is not allowed.")));

    $macao->readOne($_SESSION['id_table']);
    $player->readCurrent($macao->getRound());

    if ($player->getId() != $_SESSION['id_player'])
        die(json_encode(array('status' => 0, 'message' => "Is not your turn " . $_SESSION['id_player'] . ", is " . $player->getName() . " [" . $player->getId() . "] turn.")));

    if (!$macao->checkCards($player, $data->cards))
        die(json_encode(array('status' => 666, 'cards' => $player->getCards(), 'message' => "It's not your cards! YOU ARE A CHEATER!")));

    if (!$macao->verify($data->cards))
        die(json_encode(array('status' => 0, 'message' => "This cards is not right.")));

    $win = false;
    if ($player->removeCards($data->cards) == 0) {
        $win = true;
        if (!isset($this->details['rank']))
            $this->details['rank'] = array($_SESSION['id_player']);
        else array_push($this->details['rank'], $_SESSION['id_player']);
    }

    if (!$player->update())
        die(json_encode(array('status' => -1, 'message' => "Unable to update player.")));
    if (!$macao->update($win))
        die(json_encode(array('status' => -1, 'message' => "Unable to update game table.")));
    if ($conn->commit())
        die(json_encode(array('status' => 1)));
    die(json_encode(array('status' => -1, 'message' => "Unable to commit.")));
} // tell the user data is incomplete
else {

    // set response code - 400 bad request
    http_response_code(400);

    // tell the user
    die(json_encode(array("status" => -2, "message" => "Unable to verify cards. Data is incomplete.")));
}