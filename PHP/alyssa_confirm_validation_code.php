<?php
require_once('alyssa_common_helper.php');

//Returns true if the current timestamp is within $minutes of vc_created_time
function vc_expired ($vc_created_time, $minutes){
    $dbtime= strtotime($vc_created_time);
    $curtime = time();//In seconds since the UNIX epoch
    if ($curtime - $dbtime > ($minutes * 60))
        return true;
    else
        return false;
}

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email = $jobj->email;
$user_vc    = $jobj->validation_code;

//Check JSON error
if (empty($user_email) || empty($user_vc)) 
    exit_with_error('JSON object error');

//Connects to mysql DB, exits if failed
$conn = connect_AlyssaDB();
$user_email = mysqli_real_escape_string($conn, $user_email);
$user_vc    = mysqli_real_escape_string($conn, $user_vc);

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

$minutes = 20;
if(vc_expired($vc_created_time, $minutes))
    exit_with_error('validation code expired');

$return_data = array("success"=>true, "message" =>'validation successful');
echo json_encode($return_data);

?>
