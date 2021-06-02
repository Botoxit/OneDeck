<?php
/**
 * User: Nicu Neculache
 * Date: 24.04.2019
 * Time: 00:19
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// include database and object files
include_once CORE . 'Database.php';
include_once API . 'objects/Septica.php';
include_once API . 'objects/Player.php';

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$septica = new Septica();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $septica->readOne($player->getIdTable(), $player->getIdTable() < 5);

    if ($septica->getRound() != $player->getId())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $details = $septica->getDetails();
    unset($details['kick']);
    if ($details['new_game'] > 0)
        $details['new_game'] = $details['new_game'] - 1;
    $septica->setDetails($details);

    if (!isset($details['round_done']) || $details['round_done'] == false)
        if($player->getId() != $details['current_start'] || $player->getId() == $details['next_start'])
            die(json_encode(array('status' => 0, 'message' => "You need to place a card on table!")));
        else $septica->end_round();

    $owned_cards = count($player->getCards());
    if ($owned_cards == 4)
        die(json_encode(array('status' => 0, 'message' => "You already have 4 cards.")));

    $cards = $septica->takeCards(4 - $owned_cards);

    $player->addCards($cards);

    $player->update();
    $septica->update(true);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    if(count($cards) == 0)
        die(json_encode(array('status' => 0, 'cards' => $cards)));
    die(json_encode(array('status' => 1, 'cards' => $cards)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}
