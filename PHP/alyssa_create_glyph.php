<?php
//IMPORTANT:
//This API assumes 'fontname' exists and is the ACTIVE font for the user

require_once('alyssa_common_helper.php');

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$conn = connect_AlyssaDB();
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
$user_charname = mysqli_real_escape_string($conn, $jobj->charname);
$user_image    = mysqli_real_escape_string($conn, $jobj->image);

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
$transaction_ok = true;

//Step 1: Update last modified timestamp of corresponding font 
$stmt1 = "UPDATE Font SET font_last_modified_time = NOW() WHERE font_id = '$user_font_id' ";

//Step 2: Insert new glyph info into DB and update activeness
$stmt2 = "UPDATE Glyph SET glyph_active = FALSE WHERE charname = '$user_charname' ";
$stmt3 = "INSERT INTO Glyph VALUES (NULL, '$user_font_id', '$user_charname', NULL, TRUE)";
$stmt4 = "SELECT glyph_id FROM Glyph WHERE font_id = '$user_font_id' AND charname = '$user_charname'";
if(!mysqli_query($conn, $stmt1)) $transaction_ok = false;
if(!mysqli_query($conn, $stmt2)) $transaction_ok = false;
if(!mysqli_query($conn, $stmt3)) $transaction_ok = false;

$user_glyph_id = 0; //Needed to form the glyph file name
if(!($result = mysqli_query($conn, $stmt4)) ) 
    $transaction_ok = false;
else 
    $user_glyph_id = mysqli_fetch_array($result, MYSQLI_ASSOC)['glyph_id'];

if(!$transaction_ok) {
    mysqli_rollback($conn); 
    exit_with_error('DB operation error at updating font and glyph states'); 
}

//Step 3: Prepare glyph image and store to disk
if ($user_image->content_type != "image/jpeg") exit_with_error('not a JPEG image');
$path = $g_USER_DATA_PATH.'/'.$user_id.'/'.$user_fontname.'/'.$user_glyph_id.'.jpeg';
$user_image_data = base64_decode( str_replace(' ', '+', $user_image->file_data) );

if(!file_put_contents($path, $user_image_data)){
    mysqli_rollback($conn); 
    exit_with_error('DB operation error at updating font and glyph states'); 
} 

//TODO:
//Step 4: Update font file on disk

//If something goes wrong, rollback transaction and call exit_with_error()

//If everything is OK so far
mysqli_commit($conn);
$return_data = array("success"=>true, "message" =>'glyph updated successfully');
echo json_encode($return_data);
?>
