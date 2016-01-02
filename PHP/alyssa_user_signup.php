<?php
function entry_exists($conn, $table, $column, $value){
    $stmt = "SELECT * FROM $table WHERE $column = '$value'"; 
    $result = mysqli_query($conn, $stmt);
    if (mysqli_num_rows($result) > 0) {
        return true;
    } else {
        return false;
    }
}

function exit_with_error ($error_msg){
    $return_data = array("success"=>false, "message" =>$error_msg);
    echo json_encode($return_data);
    exit();
}

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email = $jobj->email;
$user_psw = $jobj->password;
$user_nickname = $jobj->nickname;

//Check JSON error
if (empty($user_email) || empty($user_psw) || empty($user_nickname) ) exit_with_error('JSON object error');

if (!($ini_array = parse_ini_file(".db_config.ini")) ) exit_with_error('Parsing ini file failed');


$conn =mysqli_connect($ini_array['host'], 
    $ini_array['username'], 
    $ini_array['password'], 
    $ini_array['schema']);

//$conn_details = "$ini_array['host']"."$ini_array['username']"."$ini_array['password']"."$ini_array['schema']";

if (mysqli_connect_errno()) exit_with_error('DB connection error: Error No: '.mysqli_connect_errno());

//DB connection sucessful, need to prevent SQL injection
$user_email = mysqli_real_escape_string($conn, $user_email);
$user_nickname = mysqli_real_escape_string($conn, $user_nickname);
$user_psw = mysqli_real_escape_string($conn, $user_psw);

//First check if username exists already
if (entry_exists($conn, 'User', 'user_email', $user_email)) exit_with_error('user email exists already');
if (entry_exists($conn, 'User', 'user_nickname', $user_nickname)) exit_with_error('user nickname exists already');

$user_psw_encoded = password_hash($user_psw, PASSWORD_DEFAULT);
$stmt = "INSERT INTO User (user_email, user_password, user_nickname) Values ('$user_email', '$user_psw_encoded', '$user_nickname')";
echo $stmt;
if (mysqli_query($conn, $stmt)){
    $return_data = array("success"=>true, "message" =>'user data created successfully');
    echo json_encode($return_data);
} else {
    exit_with_error('Insert into DB failed');
}
//$stmt = mysqli_prepare($conn, "INSERT INTO User Values (NULL, ?, ?, ?) "); 
//mysqli_stmt_bind_param($stmt, "sss", $user_email, $user_psw_encoded, $user_nickname));  
//mysqli_stmt_execute($stmt));
?>
