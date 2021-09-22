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
include_once CORE . 'Database.php';
include_once API . 'objects/Macao.php';
include_once API . 'objects/Player.php';

if(!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$macao = new Macao();
$player = new Player();
// get posted data
$post = json_decode(file_get_contents("php://input"));

try {
    // input validation
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
    // do the rules allow multiple cards to be placed simultaneously?
    if (count($post->cards) > 1 && !$rules['deck'])
        die(json_encode(array('status' => 0, 'message' => "Decks is not allowed.")));

    // it's this player's turn?
    if ($player->getId() != $macao->getRound())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));
    $details = $macao->getDetails();
    unset($details['kick']);
    $macao->setDetails($details);

    // he is forced to wait?
    if (isset($details['waiting']) && isset($details['waiting'][$player->getId()]))
        die(json_encode(array('status' => 0, 'message' => "You are not allowed to do this.")));

    if (!$macao->checkCards($player, $post->cards))
        die(json_encode(array('status' => 666, 'cards' => $player->getCards(), 'message' => "It's not your cards! YOU ARE A CHEATER!")));

    if (!$macao->verify($post->cards, $symbol))
        die(json_encode(array('status' => 0, 'message' => "This cards is not right.")));

    // remove cards from the player's hand and check the winning condition
    $win = false;
    if ($player->removeCards($post->cards) == 0) {
        $details = $macao->getDetails();
        $win = true;
        if (!isset($details['rank']))
            $details['rank'] = array(array('id' => $player->getId(), 'name' => $player->getName()));
        else array_push($details['rank'], array('id' => $player->getId(), 'name' => $player->getName()));
        $macao->setDetails($details);
    }
    $player->update();
    $macao->update(true, $win);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}