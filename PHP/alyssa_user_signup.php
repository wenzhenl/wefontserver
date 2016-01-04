<?php
require_once('alyssa_common_helper.php');

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = $jobj->email;
$user_psw      = $jobj->password;
$user_nickname = $jobj->nickname;

//Check JSON error
if (empty($user_email) || empty($user_psw) || empty($user_nickname) ) 
    exit_with_error('JSON object error');

//Connects to mysql DB, exits if failed
$conn = connect_AlyssaDB();

$user_email    = mysqli_real_escape_string($conn, $user_email);
$user_nickname = mysqli_real_escape_string($conn, $user_nickname);
$user_psw      = mysqli_real_escape_string($conn, $user_psw);

//First check if username exists already
if (entry_exists($conn, 'User', 'user_email', $user_email)) 
    exit_with_error('user email exists already');
if (entry_exists($conn, 'User', 'user_nickname', $user_nickname)) 
    exit_with_error('user nickname exists already');

$user_psw_encoded = password_hash($user_psw, PASSWORD_DEFAULT);
$stmt = 'INSERT INTO User (user_email, user_password, user_nickname) Values '.
   "('$user_email', '$user_psw_encoded', '$user_nickname')";
exec_query($conn, $stmt);

//Create a directory for each new user
$stmt    = "SELECT user_id FROM User WHERE user_email = '$user_email' ";
$result  = exec_query ($conn, $stmt);
$row     = mysqli_fetch_array($result, MYSQLI_ASSOC);
$user_id = $row['user_id'];
$path    = $g_USER_DATA_PATH.'/'.$user_id;

if (!mkdir($path, 0755, true)){
    exec_query($conn, "DELETE FROM User WHERE user_id = '$user_id'");
    exit_with_error('Failed to create directory at path : '.$path);
}

$return_data = array("success"=>true, "message" =>'user data created successfully');
echo json_encode($return_data);

?>
