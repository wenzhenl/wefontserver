<?php
require_once('alyssa_common_helper.php');

function query_num_finished_chars($conn, $font_id){
    $stmt   = "SELECT * FROM Glyph WHERE font_id = '$font_id' AND glyph_active is true ";
    $result = exec_query ($conn, $stmt);
    return mysqli_num_rows($result);
}

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email = $jobj->email;
$user_psw   = $jobj->password;

if (empty($user_email) || empty($user_psw)) 
    exit_with_error('JSON object error');

$conn = connect_AlyssaDB();
$user_email = mysqli_real_escape_string($conn, $user_email);
$user_psw   = mysqli_real_escape_string($conn, $user_psw);

$stmt   = 'SELECT user_id, user_nickname, user_password '. 
    " FROM User WHERE user_email = '$user_email' ";
$result = exec_query ($conn, $stmt);

if (mysqli_num_rows($result) == 0) 
    exit_with_error('user email does not exist');

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$user_id          = $row['user_id'];
$user_nickname    = $row['user_nickname'];
$user_psw_encoded = $row['user_password'];

if(!password_verify($user_psw, $user_psw_encoded)) 
    exit_with_error('user password incorrect');

$return_data = array("success"=>true, "message" =>'user login successful');

//Login successful, now return the font related info if available
$stmt = "SELECT font_id, fontname, font_active FROM Font WHERE user_id = '$user_id' ";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0) {
    //No active font associated with user
    echo json_encode($return_data);
} else {
    //Return font info
    $return_data['all_fonts_info'] = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC) ){
        $font_id     = $row['font_id'];
        $fontname    = $row['fontname'];
        $font_active = $row['font_active'];
        if ($font_active == true){
            $return_data['active'] = $fontname;
        }
        //Query num of finished chars in each font;
        $num_of_finished_chars = query_num_finished_chars($conn, $font_id);
        $item = array("fontname" => $fontname, "num_of_finished_chars" => $num_of_finished_chars);
        array_push($return_data['all_fonts_info'], $item); 
    }
    echo json_encode($return_data);
}

?>
