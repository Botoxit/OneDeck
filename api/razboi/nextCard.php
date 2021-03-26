<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// include database and object files
include_once CORE . 'Database.php';
include_once API . 'objects/Razboi.php';
include_once API . 'objects/Player.php';

if (!isset($_SESSION['id_player']))
    die(json_encode(array("status" => -21, "message" => "id_player is not set!")));

$conn = Database::getConnection();
$razboi = new Razboi();
$player = new Player();

try {
    $player->readOne($_SESSION['id_player']);
    $razboi->readOne($player->getIdTable(), true);

    if ($razboi->getRound() != $player->getId())
        die(json_encode(array('status' => 0, 'message' => "Is not your turn.")));

    $player_cards = $player->getCards();

    if ($razboi->isWar()) {
        $details = $razboi->getDetails();
        if (!in_array($player->getId(), $details['inWar']))
            die(json_encode(array('status' => 0, 'message' => "You are not in this war.")));
        unset($details['inWar'][array_search($player->getId(), $details['inWar'])]);
        $razboi->setDetails($details);
        $count = intdiv($razboi->getPlayerCard($player->getId()), 10);
        if ($count <= 0)
            die(json_encode(array('status' => 0, 'message' => "You don't have cards on table.")));
        $cards = array_splice($player_cards, 0, $count);
    } else {
        $cards = array($player_cards[0]);
    }
    $player->removeCards($cards);
    $razboi->nextCard($player, $cards);


    $player->update();
    $razboi->update(true, count($player_cards) == 0);
    if (!$conn->commit())
        throw new GameException("Commit work failed, $conn->errno: $conn->error", 4);
    die(json_encode(array('status' => 1, 'cards' => $cards)));
} catch (GameException $e) {
    GameException::exitMessage($e->getCode());
}