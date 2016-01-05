<?php
require_once('alyssa_common_helper.php');

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$book_title = mysqli_real_escape_string($jobj->book_title);

if(empty($book_title)) exit_with_error('JSON object error');

$book_path = ALYSSA_BOOK_PATH.'/'.$book_title.'.txt';

if(!($book_content = file_get_contents($book_path))){
    exit_with_error('fails to load book at path '.$book_path);
} else {
    $return_data = array("success"=>true, "message" =>'book fetched successfully', "book_title"=>$book_title, "book"=>base64_encode($book_content));
    echo json_encode($return_data);
}

?>
