<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_GET["ver"] < 0.4)
    die(json_encode(array("status" => 0)));
die(json_encode(array("status" => 1)));
