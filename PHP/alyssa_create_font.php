<?php
require_once('alyssa_common_helper.php');

function create_font_dir ($user_id, $user_fontname) {
    $path = '/home/ubuntu/AlyssaData/Users/'.$user_id.'/'.$user_fontname;
    if (mkdir($path, 0755, true)) return true;
    else return false;
}

//Performs the DB operation and mkdir in a single transaction
function exec_query_and_create_dir($conn, $user_id, $user_fontname){
    //Create directory with font_id under user_id
    if (create_font_dir($user_id, $user_fontname)) {
        mysqli_commit($conn);
        $return_data = array("success"=>true, "message" =>'font updated successfully');
        echo json_encode($return_data);
    } else {
        mysqli_rollback($conn);
        exit_with_error('fails to create font directory, font not updated'); 
    }
}

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$conn = connect_AlyssaDB();
$user_email = mysqli_real_escape_string($conn, $jobj->email);
$user_psw   = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname   = mysqli_real_escape_string($conn, $jobj->fontname);
$copyright  = mysqli_real_escape_string($conn, $jobj->copyright);
$version    = mysqli_real_escape_string($conn, $jobj->version);

if (empty($user_email) || empty($user_psw) || empty($user_fontname) || empty($copyright) || empty($version) )
    exit_with_error('JSON object error');    

$stmt = "SELECT user_id, user_password FROM User WHERE user_email = '$user_email' ";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0) 
    exit_with_error('user email does not exist');
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$user_id = $row['user_id'];
if(!password_verify($user_psw, $row['user_password'])) 
    exit_with_error('user password incorrect');

$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' ";
$result = exec_query ($conn, $stmt);

if (mysqli_num_rows($result) == 0){
    //Add new font
    mysqli_autocommit($conn, false);
    $stmt = "INSERT INTO Font VALUES (NULL, '$user_id', '$user_fontname', '$copyright', '$version', NULL, NULL, TRUE) ";
    if(mysqli_query($conn, $stmt)) {
        exec_query_and_create_dir($conn, $user_id, $user_fontname);
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
    $transaction_ok = true;
    mysqli_autocommit($conn, false);
    $stmt1 = "INSERT INTO Font VALUES (NULL, '$user_id', '$user_fontname', '$copyright', '$version', NULL, NULL, TRUE) ";
    $stmt2 = "UPDATE Font SET font_active = FALSE WHERE font_id = '$active_font_id' ";
    if(!mysqli_query($conn, $stmt1)) $transaction_ok = false;
    if(!mysqli_query($conn, $stmt2)) $transaction_ok = false;
    if($transaction_ok) {
        exec_query_and_create_dir($conn, $user_id, $user_fontname);
    } else {
        mysqli_rollback($conn); 
        exit_with_error('DB operation error, font not updated'); 
    }
} 

mysqli_close($conn);

?>
