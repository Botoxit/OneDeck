<?php
/**
 * User: Nicu Neculache
 * Date: 16.04.2019
 * Time: 14:26
 */

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once '../config/DataBase.php';
include_once '../objects/Table.php';

// instantiate database and product object
$database = new DataBase();
$conn = $database->getConnection();

// initialize object
$table = new Table($conn);

// query products
$result = $table->read();
$rowCount = $result->num_rows;

// check if more than 0 record found
if ($rowCount > 0) {

    // products array
    $products_arr = array();
    $products_arr["records"] = array();

    // retrieve our table contents
    // fetch() is faster than fetchAll()
    // http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        // extract row
        // this will make $row['name'] to
        // just $name only
        extract($row);

        $product_item = array(
            "id" => $id,
            "name" => $name,
            "description" => html_entity_decode($description),
            "price" => $price,
            "category_id" => $category_id,
            "category_name" => $category_name
        );

        array_push($products_arr["records"], $product_item);
    }

    // set response code - 200 OK
    http_response_code(200);

    // show products data in json format
    echo json_encode($products_arr);
} else {

    // set response code - 404 Not found
    http_response_code(404);

    // tell the user no products found
    echo json_encode(array("message" => "No products found."));
}
