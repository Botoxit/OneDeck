<?php

include_once 'core/Database.php';

$conn = Database::getConnection();
date_default_timezone_set('Europe/Bucharest');

$stmt = $conn->prepare("CALL `delete_inactive_tables`();");
$message = "Log message";

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        if(!$conn->commit())
            $message = "Commit work failed, $conn->errno: $conn->error";
        else $message = "I deleted " . $stmt->affected_rows/2 . " inactive tables.";
    }
    else die();
} else $message = "I can't delete inactive tables $stmt->errno: $stmt->error";

$date = date("Y-m-d h:m:s");
error_log("[$date] " . $message . PHP_EOL, 3, "cronJob.log");