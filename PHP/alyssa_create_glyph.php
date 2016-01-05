<?php
//IMPORTANT:
//This API assumes 'fontname' exists and is the ACTIVE font for the user

require_once('alyssa_common_helper.php');

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
$user_charname = mysqli_real_escape_string($conn, $jobj->charname);
// $user_image    = mysqli_real_escape_string($conn, $jobj->image);
$user_image    = $jobj->image;

// $dbg_str = 'Calling create_glyph:'."\n".$user_email."\n".
//     $user_password."\n".$user_fontname."\n".$user_charname."\n".$user_image;
// if (!file_put_contents("./home/ubuntu/Alyssa_DEBUG/PHP_DEBUG.txt", $dbg_str, FILE_APPEND))
//     exit_with_error('failed to write DBG file on server');

if (empty($user_email) || empty($user_password) || 
    empty($user_fontname) || empty($user_charname) || empty($user_image) )
    exit_with_error('JSON object error');    

$row = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

//Obtain font_id, which is not provided by the client
$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' ".
    "AND font_active IS TRUE AND fontname = '$user_fontname' ";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0)
    exit_with_error('fontname not found or is inactive');
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$user_font_id  = $row['font_id'];

/* This single TRANSACTION performs:
 * 1. Update last modified timestamp of corresponding font 
 * 2. Insert new glyph info into DB and update activeness
 * 3. Prepare glyph image and store to disk
 * 4. Update font file on disk
 * */
mysqli_autocommit($conn, false);

//Step 1: Update last modified timestamp of corresponding font 
$stmt1 = "UPDATE Font SET font_last_modified_time = NOW() WHERE font_id = '$user_font_id' ";

//Step 2: Insert new glyph info into DB and update activeness
$stmt2 = "UPDATE Glyph SET glyph_active = FALSE WHERE font_id = '$user_font_id' ".
    "AND charname = '$user_charname' AND glyph_active IS TRUE";
$stmt3 = "INSERT INTO Glyph VALUES (NULL, '$user_font_id', '$user_charname', NULL, TRUE)";
$stmt4 = "SELECT glyph_id FROM Glyph WHERE font_id = '$user_font_id' ".
    " AND charname = '$user_charname' AND glyph_active IS TRUE";

if(!mysqli_query($conn, $stmt1)) rollback_and_exit($conn, 'DB op failure: unable to update font last modified time');
if(!mysqli_query($conn, $stmt2)) rollback_and_exit($conn, 'DB op failure: unable to update glyph activeness');
if(!mysqli_query($conn, $stmt3)) rollback_and_exit($conn, 'DB op failure: unable to insert new glyph');
if(!($stmt4_result = mysqli_query($conn, $stmt4)) ) rollback_and_exit($conn, 'DB op failure: unable to select glyph_id');


//Step 3: Prepare glyph image and store to disk
$user_glyph_id = mysqli_fetch_array($stmt4_result, MYSQLI_ASSOC)['glyph_id'];
if ($user_image->content_type != "image/jpeg") exit_with_error('not a JPEG image');
$image_path = ALYSSA_USER_PATH.'/'.$user_id.'/'.$user_font_id.'/'.$user_glyph_id.'.jpeg';
$font_path  = ALYSSA_USER_PATH.'/'.$user_id.'/'.$user_font_id.'/'.ALYSSA_DEFAULT_FONTFILE;
$user_image_data = base64_decode( str_replace(' ', '+', $user_image->file_data) );

if(!file_put_contents($image_path, $user_image_data)) rollback_and_exit($conn, 'failed to write glyph image at path '.$image_path);

//TODO:
//Step 4: Update font file on disk
$output = shell_exec(ALYSSA_SCRIPT_PATH."/addCharImageIntoFont.sh $user_charname $image_path $font_path");

//If something goes wrong, rollback transaction and call exit_with_error()
if(is_null($output)) {
  mysqli_rollback($conn);
  exit_with_error("fails to add glyph into font");
}

//If everything is OK so far
mysqli_commit($conn);
$return_data = array("success"=>true, "message" =>'glyph updated successfully');
echo json_encode($return_data);
?>
