<?php
require_once('alyssa_common_helper.php');

//Performs the DB operation and mkdir in a single transaction
function exec_query_and_create_dir($conn, $user_id, $user_fontname, $base_path){
    //Obtain the user_font_id
    $stmt = "SELECT font_id FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname' ";
    $result = mysqli_query($conn, $stmt);
    if(!$result) {
        mysqli_rollback($conn);
        exit_with_error('fails to insert font into DB, font not updated'); 
    }
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);//row has the active font
    $user_font_id = $row['font_id'];

    //Create directory with font_id under user_id
    $path = $base_path.'/'.$user_id.'/'.$user_font_id;
    if (mkdir($path, 0755, true)){
        mysqli_commit($conn);
        $return_data = array("success"=>true, "message" =>'font updated successfully');
        echo json_encode($return_data);
    } else {
        mysqli_rollback($conn);
        exit_with_error('fails to create font directory, font not updated'); 
    }
}


$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
$copyright     = mysqli_real_escape_string($conn, $jobj->copyright);
$version       = mysqli_real_escape_string($conn, $jobj->version);

if (empty($user_email) || empty($user_password) || empty($user_fontname) || empty($copyright) || empty($version) )
    exit_with_error('JSON object error');    

$row     = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' ";
$result = exec_query ($conn, $stmt); //contains all fonts of this user


/* This single TRANSACTION performs:
 * 1. Insert new font and Update activeness of corresponding fonts 
 * 2. Create new font folder on disk
 * */
mysqli_autocommit($conn, false);
$transaction_ok = true;

if (mysqli_num_rows($result) == 0){
    //Add new font
    $stmt = "INSERT INTO Font VALUES (NULL, '$user_id', '$user_fontname', '$copyright', '$version', NULL, NOW(), TRUE) ";
    if(mysqli_query($conn, $stmt)) {
        exec_query_and_create_dir($conn, $user_id, $user_fontname, ALYSSA_USER_PATH);
    } else {
        mysqli_rollback($conn); 
        exit_with_error('DB operation error, font not updated'); 
    }

} else {
    //Check existing font for duplication and update activeness
    $active_font_id ='';
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
        $font_id     = $row['font_id'];
        $fontname    = $row['fontname'];
        $font_active = $row['font_active'];
        if (strcmp($fontname, $user_fontname) == 0) 
            exit_with_error('same fontname already exists');
        if ($font_active == true){
            $active_font_id = $font_id;
        }
    }

    //Insert and update activeness MUST be in a single TRANSACTION
    $stmt1 = "INSERT INTO Font VALUES (NULL, '$user_id', '$user_fontname', '$copyright', '$version', NULL, NOW(), TRUE) ";
    $stmt2 = "UPDATE Font SET font_active = FALSE WHERE font_id = '$active_font_id' ";
    if(!mysqli_query($conn, $stmt1)) $transaction_ok = false;
    if(!mysqli_query($conn, $stmt2)) $transaction_ok = false;
    if($transaction_ok) {
        exec_query_and_create_dir($conn, $user_id, $user_fontname, ALYSSA_USER_PATH);
    } else {
        mysqli_rollback($conn); 
        exit_with_error('DB operation error, font not updated'); 
    }
} 

mysqli_close($conn);

?>
