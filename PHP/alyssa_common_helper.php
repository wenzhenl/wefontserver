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

function rollback_and_exit($conn, $msg){
    mysqli_rollback($conn);
    exit_with_error($msg); 
}


function connect_AlyssaDB (){
    if (!($ini_array = parse_ini_file(".db_config.ini")) ) 
        exit_with_error('Parsing ini file failed');

    $conn =mysqli_connect($ini_array['host'], 
        $ini_array['username'], 
        $ini_array['password'], 
        $ini_array['schema']);

    if (mysqli_connect_errno()) 
        exit_with_error('DB connection error: Error No: '.mysqli_connect_errno());

    return $conn;
}

function exec_query($conn, $stmt){
    $result = mysqli_query($conn, $stmt);
    if (!result) exit_with_error('DB operation failed with '.$stmt);
    return $result;
}

//Returns true if the current timestamp is within $minutes of the old timestamp
function timestamp_expired ($old_timestamp, $minutes){
    $pasttime= strtotime($old_timestamp);
    $curtime = time();//In seconds since the UNIX epoch
    if ($curtime - $pasttime > ($minutes * 60))
        return true;
    else
        return false;
}

//Returns the user row if verified OK,
//Exits with error if not verified.
function verify_user_password($conn, $user_email, $user_password){
    $stmt = "SELECT * FROM User WHERE user_email = '$user_email' ";
    $result = exec_query ($conn, $stmt);
    if (mysqli_num_rows($result) == 0) 
        exit_with_error('user email does not exist');
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if(!password_verify($user_password, $row['user_password'])) 
        exit_with_error('user password incorrect');
    return $row;
}

//Constants
define("ALYSSA_DATA_PATH", "/home/ubuntu/AlyssaData");
define("ALYSSA_USER_PATH", "/home/ubuntu/AlyssaData/Users");
define("ALYSSA_BOOK_PATH", "/home/ubuntu/AlyssaData/Books");
define("ALYSSA_SCRIPT_PATH", "/home/ubuntu/AlyssaData/Scripts");
define("ALYSSA_DEFAULT_FONTFILE", "alyssafont.ttf");
?>
