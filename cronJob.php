<?php

define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('CORE', ROOT . 'core' . DIRECTORY_SEPARATOR);
define('API', ROOT . 'api' . DIRECTORY_SEPARATOR);

include_once CORE . 'Database.php';
include_once API . 'objects' . DIRECTORY_SEPARATOR . 'Player.php';
include_once API . 'objects' . DIRECTORY_SEPARATOR . 'Table.php';
include_once API . 'objects' . DIRECTORY_SEPARATOR . 'Game.php';
include_once API . 'objects' . DIRECTORY_SEPARATOR . 'GameException.php';

$conn = Database::getConnection();
date_default_timezone_set('Europe/Bucharest');
$start_time = strtotime("now");

/* =================================== Delete inactive tables ================================== */
$query = "SELECT t.id, t.name, t.players_limit, t.created_at, g.round FROM `game` g join `tables_list` t ON t.id = g.id where timestampdiff(SECOND,`change_at`,CURRENT_TIMESTAMP) > 300 AND g.id > 4";
$stmt = $conn->prepare($query);

if ($stmt->execute()) {
    $table_list = [];
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $table_item = array(
                "id" => $row['id'],
                "tableName" => $row['name'],
                "playersLimit" => $row['players_limit'],
                "created_at" => $row['created_at'],
                "round" => $row['round']
            );
            array_push($table_list, $table_item);
        }
        // Delete inactive tables
        $stmt = $conn->prepare("CALL `delete_inactive_tables`();");
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                if (!$conn->commit())
                    cronJobLog("Commit work failed, $conn->errno: $conn->error");
            }
        } else cronJobLog("I can't delete inactive tables $stmt->errno: $stmt->error");
        cronJobLog("I selected following inactive tables " . $stmt->affected_rows / 2 . ": " . json_encode($table_list));
    }
} else cronJobLog("I can't select inactive tables $stmt->errno: $stmt->error");


/* ============================= Kick inactive players from tables ============================= */
$query = "SELECT p.id, p.id_table, p.name, p.cards, g.round, t.host FROM `player` p join `game` g on g.id = p.id_table join `tables_list` t on t.id = p.id_table where timestampdiff(SECOND,g.change_at,CURRENT_TIMESTAMP) > 40";
$stmt = $conn->prepare($query);
$boot_friends = array(0, 0, 0, 0, 0);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows == 0)
        die();

    while ($row = $result->fetch_assoc()) {
        if ($row['id'] < 5)
            continue;

        if ($row['id_table'] < 5)
            $boot_friends[$row['id_table']]++;

        $round = json_decode($row['round'], true);
        $cards = json_decode($row['cards'], true);

        if (count($round) > 1) { // in game state
            if ($row['id_table'] < 5 && $row['id'] == $round[0]) {
                kick_player($row['id'], $row['id_table'], $cards, true);
                cronJobLog("I kick from table " . $row['id_table'] . " player " . $row['name'] . "[" . $row['id'] . "] for inactivity in game");
                $boot_friends[$row['id_table']]--;
            }
        } else { // in wait for player state
            if ($row['id'] != $row['host'] && (!isset($cards['ready']) || $cards['ready'] != true)) {
                kick_player($row['id'], $row['id_table'], $cards, false);
                cronJobLog("I kick from table " . $row['id_table'] . " player " . $row['name'] . "[" . $row['id'] . "] for inactivity in lobby");
                if ($row['id_table'] < 5)
                    $boot_friends[$row['id_table']]--;
            }
        }
    }
} else cronJobLog("Unable to read tables data, $stmt->errno: $stmt->error");

$delete_chat_ids = "";
for ($i = 1; $i < 5; $i++) {
    if ($boot_friends[$i] == 0) {
        if($delete_chat_ids == "")
            $delete_chat_ids .= $i;
        else $delete_chat_ids .= ",$i";
    }
}
if ($delete_chat_ids != "") {
    $query = "UPDATE `game` SET chat = '[]' WHERE id IN ($delete_chat_ids)";
    $stmt = $conn->prepare($query);

    if (!$stmt->execute())
        cronJobLog("Unable to clean chat for boot table, $stmt->errno: $stmt->error");
}

if (!$conn->commit())
    cronJobLog("Commit work failed, $conn->errno: $conn->error");

$duration = strtotime("now") - $start_time;
if ($duration > 1) {
    cronJobLog("Long run time: $duration");
}

// Functions
function cronJobLog($message)
{
    $date = date("Y-m-d H:i:s");
    error_log("[$date] $message" . PHP_EOL, 3, ROOT . 'cronJob.log');
}

function kick_player($id_player, $id_table, $cards, $playing)
{
    $player = new Player();
    $table = new Table();
    $game = new Game();
    try {
        $player->setId($id_player);
        $player->setIdTable($id_table);
        $player->setCards($cards);

        // modify table data
        $table->readOne($id_table);
        $playerLimit = $table->getPlayersLimit() - 10;
        $table->setPlayersLimit($playerLimit);
        if ($game->getHost() == $player->getId())
            $table->newHost();
        $table->update();

        if ($playing) {
            $game->readOne($id_table);
            $game->deletePlayer($player);
            $game->update(true, false, true);
        }

        $player->delete();
    } catch (GameException $e) {
        GameException::exitMessage($e->getCode());
    }
}