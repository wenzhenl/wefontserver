<?php
require_once('alyssa_common_helper.php');

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email   = mysqli_real_escape_string($conn, trim($jobj->email);
$user_vc      = mysqli_real_escape_string($conn, trim($jobj->validation_code));
$new_password = mysqli_real_escape_string($conn, trim($jobj->new_password));

if (empty($user_email) || empty($user_vc) || empty($new_password)) 
    exit_with_error('JSON object error');

$stmt   = 'SELECT validation_code, vc_created_time '. 
    " FROM UserValidation WHERE vc_email = '$user_email' ";
$result = exec_query ($conn, $stmt);

if (mysqli_num_rows($result) == 0) 
    exit_with_error('user email does not exist');

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$vc_encoded = $row['validation_code'];
$vc_created_time = $row['vc_created_time'];

if(!password_verify($user_vc, $vc_encoded)) 
    exit_with_error('validation code incorrect');

$minutes = 3;
if(timestamp_expired($vc_created_time, $minutes))
    exit_with_error('validation code expired');

//VC is correct and up to date, now update the password
$user_psw_encoded = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = "Update User SET user_password = '$user_psw_encoded' ".
    " WHERE user_email = '$user_email' ";
exec_query($conn, $stmt);

$return_data = array("success"=>true, "message" =>'password reset successful');
echo json_encode($return_data);

?>
