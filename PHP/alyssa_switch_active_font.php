<?php

//NOTE: when switching fonts, last modified time of the font is NOT updated

require_once('alyssa_common_helper.php');

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$conn = connect_AlyssaDB();
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);

if (empty($user_email) || empty($user_password) || empty($user_fontname))
    exit_with_error('JSON object error');    

$row = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

//Obtain font_id, which is not provided by the client
$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname' ";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0)
    exit_with_error('fontname not found with this user'); 
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);//row has the active font
$user_font_id  = $row['font_id'];

mysqli_autocommit($conn, false);
$transaction_ok = true;

$stmt1 = "UPDATE Font SET font_active = FALSE WHERE user_id = '$user_id' AND font_active IS TRUE ";
$stmt2 = "UPDATE Font SET font_active = TRUE WHERE font_id = '$user_font_id' ";
if(!mysqli_query($conn, $stmt1)) $transaction_ok = false;
if(!mysqli_query($conn, $stmt2)) $transaction_ok = false;
if(!$transaction_ok) {
    mysqli_rollback($conn); 
    exit_with_error('DB operation error at changing font activeness'); 
} else {
    mysqli_commit($conn);
    $return_data = array("success"=>true, "message" =>'font activated successfully');
    echo json_encode($return_data);
}

?>
