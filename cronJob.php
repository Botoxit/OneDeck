<?php

include_once 'core/Database.php';

$conn = Database::getConnection();
date_default_timezone_set('Europe/Bucharest');

$message = "Log message";
$date = date("Y-m-d H:i:s");

$query = "SELECT t.id, t.name, t.players_limit, t.created_at, g.round FROM `game` g join `tables_list` t ON t.id = g.id where timestampdiff(SECOND,`change_at`,CURRENT_TIMESTAMP) > 300 AND g.id > 1";
$stmt = $conn->prepare($query);
if ($stmt->execute()) {
    $table_list = [];
    $result = $stmt->get_result();
    if ($result->num_rows == 0)
        die();
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
    error_log("[$date] I selected following inactive tables: " . json_encode($table_list) . PHP_EOL, 3, "cronJob.log");
} else $message = "I can't select inactive tables $stmt->errno: $stmt->error";

$stmt = $conn->prepare("CALL `delete_inactive_tables`();");
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        if (!$conn->commit())
            $message = "Commit work failed, $conn->errno: $conn->error";
        else die();
    } else die();
} else $message = "I can't delete inactive tables $stmt->errno: $stmt->error";

error_log("[$date] " . $message . PHP_EOL, 3, "cronJob.log");