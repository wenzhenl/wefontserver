<?php
require_once('alyssa_common_helper.php');

function query_num_finished_chars($conn, $font_id){
    $stmt   = "SELECT * FROM Glyph WHERE font_id = '$font_id' AND glyph_active is true ";
    $result = exec_query ($conn, $stmt);
    return mysqli_num_rows($result);
}

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, trim($jobj->email));
$user_password = mysqli_real_escape_string($conn, trim($jobj->password));

if (empty($user_email) || empty($user_password)) 
    exit_with_error('JSON object error');

$row = verify_user_password($conn, $user_email, $user_password);//returns user row if verified
$user_id = $row['user_id'];
$user_nickname = $row['user_nickname'];

$return_data = array("success"=>true, "message" =>'user login successful', "nickname" => "$user_nickname");

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
            $return_data['active_font'] = $fontname;
        }
        //Query num of finished chars in each font;
        $num_of_finished_chars = query_num_finished_chars($conn, $font_id);
        $item = array("fontname" => $fontname, "num_of_finished_chars" => $num_of_finished_chars);
        array_push($return_data['all_fonts_info'], $item); 
    }
    echo json_encode($return_data);
}
?>
