<?php
require_once('alyssa_common_helper.php');

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, trim($jobj->email));
$user_password = mysqli_real_escape_string($conn, trim($jobj->password));
$user_fontname = mysqli_real_escape_string($conn, trim($jobj->fontname));
$user_lmt      = mysqli_real_escape_string($conn, trim($jobj->last_modified_time));

if (empty($user_email) || empty($user_password) || empty($user_fontname))
    exit_with_error('JSON object error');

$row = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname'";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0) exit_with_error('the required font is not found');
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$user_font_id  = $row['font_id'];
$db_lmt      = $row['font_last_modified_time'];

//Needs to sent back fresh font file if:
//1. User did not provide font last modified time (user_lmt is empty string)
//2. User's lmt is different from the DB's lmt 
$needs_fresh_font = empty($user_lmt)? true : (strcmp($db_lmt, $user_lmt) == 0? false: true);

if ($needs_fresh_font){
    $font_path = ALYSSA_USER_PATH.'/'.$user_id.'/'.$user_font_id.'/'.ALYSSA_DEFAULT_FONTFILE;
    if(!($font_data = file_get_contents($font_path)))
        exit_with_error('fails to load font at path '.$font_path);
    $return_data = array("success"=>true, "message" =>'font fetched successfully',
        "font" => base64_encode($font_data), "last_modified_time" => $db_lmt);
} else {
    $return_data = array("success"=>false, "message" =>'user font is fresh');
}

echo json_encode($return_data);

?>

