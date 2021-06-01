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
include_once API . 'objects/Septica.php';
include_once API . 'objects/Player.php';

if(!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$septica = new Septica();
$player = new Player();
// get posted data
$post = json_decode(file_get_contents("php://input"));

try {
    if (empty($post->cards))
        throw new GameException("Bad request, post data is missing", 8);
    if (count($post->cards) > 1)
        die(json_encode(array('status' => 0, 'message' => "Decks is not allowed.")));

    $player->readOne($_SESSION['id_player']);
    $septica->readOne($player->getIdTable(), true);

    if ($player->getId() != $septica->getRound())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $details = $septica->getDetails();
    if(isset($details['round_done']) && $details['round_done'] == true)
        die(json_encode(array('status' => 0, 'message' => "Round done. Take cards!")));
    unset($details['kick']);
    $septica->setDetails($details);

    if (!$septica->checkCards($player, $post->cards))//        throw new GameException("Cheater detected: id: " . $player->getId() . ", name: " . $player->getName(),9);
        die(json_encode(array('status' => 666, 'cards' => $player->getCards(), 'message' => "It's not your cards! YOU ARE A CHEATER!")));

    if (!$septica->verify($player->getId(), $post->cards[0]))
        die(json_encode(array('status' => 0, 'message' => "This cards is not right.")));

    $cards_count = $player->removeCards($post->cards);
    if($cards_count == 0 && $septica->getDeckCount() < $septica->getPlayerCount())
    {
        $details = $septica->getDetails();
        if (!isset($details['rank']))
            $details['rank'] = array(array('id' => $player->getId(), 'name' => $player->getName()));
        else array_push($details['rank'], array('id' => $player->getId(), 'name' => $player->getName()));
        $septica->setDetails($details);
    }

    $player->update();
    $septica->update(true, $cards_count == 0);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}