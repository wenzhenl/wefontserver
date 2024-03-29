<?php
require_once('alyssa_common_helper.php');

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$book_title = mysqli_real_escape_string($conn, trim($jobj->book_title));

if(empty($book_title)) exit_with_error('1201');

$book_path = ALYSSA_BOOK_PATH.'/'.$book_title.'.txt';

if(!($book_data = file_get_contents($book_path))){
    exit_with_error('1202');
} else {
    $return_data = array("success"=>true, "message" =>'book fetched successfully', "book_title"=>$book_title, "book"=>base64_encode($book_data));
    echo json_encode($return_data);
}

?>
