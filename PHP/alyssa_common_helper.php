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


function connect_AlyssaDB (){
    if (!($ini_array = parse_ini_file(".db_config.ini")) ) exit_with_error('Parsing ini file failed');

    $conn =mysqli_connect($ini_array['host'], 
        $ini_array['username'], 
        $ini_array['password'], 
        $ini_array['schema']);

    if (mysqli_connect_errno()) exit_with_error('DB connection error: Error No: '.mysqli_connect_errno());
    return $conn;
}

function exec_query($conn, $stmt){
    $result = mysqli_query($conn, $stmt);
    if (!result) exit_with_error('DB query failed with: '.$stmt);
    return $result;
}
?>
