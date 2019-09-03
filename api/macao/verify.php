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

$conn = DataBase::getConnection();
$macao = new Macao();
$player = new Player();
// get posted data
$post = json_decode(file_get_contents("php://input"));

try {
    if (empty($post->cards))
        throw new GameException("Bad request, post data is missing", 8);
    $symbol = 0;
    if (!empty($post->status) && ($post->status < 0 || $post->status > 4))
        throw new GameException("Bad request, post data is missing", 8);
    elseif (!empty($post->status))
        $symbol = $post->status;
    $player->readOne($_SESSION['id_player']);
    $macao->readOne($player->getIdTable(), true);
    $rules = $macao->getRules();
    if (count($post) > 1 && !$rules['deck'])
        die(json_encode(array('status' => 0, 'message' => "Decks is not allowed.")));

    if ($player->getId() != $macao->getRound())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));
    $details = $macao->getDetails();
    if (isset($details['waiting']) && isset($details['waiting'][$player->getId()]))
        die(json_encode(array('status' => 0, 'message' => "You are not allowed to do this.")));

    if (!$macao->checkCards($player, $post))//        throw new GameException("Cheater detected: id: " . $player->getId() . ", name: " . $player->getName(),9);
        die(json_encode(array('status' => 666, 'cards' => $player->getCards(), 'message' => "It's not your cards! YOU ARE A CHEATER!")));

    if (!$macao->verify($post, $symbol))
        die(json_encode(array('status' => 0, 'message' => "This cards is not right.")));

    $win = false;
    if ($player->removeCards($post) == 0) {
        $win = true;
        if (!isset($this->details['rank']))
            $this->details['rank'] = array($player->getId());
        else array_push($this->details['rank'], $player->getId());
    }
    $player->update();
    $macao->update($win);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1)));
} catch (GameException $e) {
    switch ($e->getCode()) {
        case 1:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read player.")));
        case 2:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read macao game data.")));
        case 3:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update macao game data.")));
        case 4:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to commit.")));
        case 5:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to read ready player.")));
        case 6:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Unable to update player.")));
        case 8:
            die(json_encode(array("status" => -$e->getCode(), "message" => "Bad request, data is missing.")));
        case 9:
            die(json_encode(array("status" => -$e->getCode(), "message" => "It's not your cards! YOU ARE A CHEATER!")));
    }
}