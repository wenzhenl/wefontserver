<?php
require_once('alyssa_common_helper.php');

function write_glyph_image ($user_id, $user_font_id, $user_glyph_id) {
    $path = $g_USER_DATA_PATH.'/'.$user_id.'/'.$user_font_id.'/';
    if (mkdir($path, 0755, true)) return true;
    else return false;
}

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$conn = connect_AlyssaDB();
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
$user_charname = mysqli_real_escape_string($conn, $jobj->charname);
$user_image    = mysqli_real_escape_string($conn, $jobj->image);

if (empty($user_email) || empty($user_password) || 
    empty($user_fontname) || empty($charname) || empty($image) )
    exit_with_error('JSON object error');    

$row = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND font_active IS TRUE";
$result = exec_query ($conn, $stmt);
if (mysqli_num_rows($result) == 0)
    exit_with_error('no font is found with this user'); 

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);//row has the active font
$active_font_id  = $row['font_id'];
$active_fontname = $row['fontname'];
$user_font_id = $active_font_id;

//Find the user_font_id if user font is not currenlty active
if (strcmp($active_fontname, $user_fontname) !=0) {
    $stmt = "SELECT font_id FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname' ";
    $result = exec_query ($conn, $stmt);
    if (mysqli_num_rows($result) == 0)
        exit_with_error('fontname specified is not found with this user'); 
    $user_font_id = $row['font_id'];
}

/* This single TRANSACTION performs:
 * 1. Update activeness of corresponding fonts 
 * 2. Insert new glyph info into DB and update activeness
 * 3. Prepare glyph image and store to disk
 * 4. Create new font file on disk
 * */
mysqli_autocommit($conn, false);
$transaction_ok = true;

//Step 1: Update font activeness only if user font is not the current active font
if (strcmp($active_fontname, $user_fontname) !=0) {
    $stmt1 = "UPDATE Font SET font_active = FALSE WHERE user_id = '$user_id' AND font_active IS TRUE ";
    $stmt2 = "UPDATE Font SET font_active = TRUE  WHERE user_id = '$user_id' AND fontname = '$user_fontname' ";
    if(!mysqli_query($conn, $stmt1)) $transaction_ok = false;
    if(!mysqli_query($conn, $stmt2)) $transaction_ok = false;
    if(!$transaction_ok) {
        mysqli_rollback($conn); 
        exit_with_error('DB operation error at updating font state'); 
    }
}

//Step 2: Insert new glyph info into DB and update activeness
$stmt3 = "UPDATE Glyph SET glyph_active = FALSE WHERE charname = '$user_charname' ";
$stmt4 = "INSERT INTO Glyph VALUES (NULL, '$user_font_id', '$user_charname', NULL, TRUE)";
$stmt5 = "SELECT glyph_id FROM Glyph WHERE font_id = '$user_font_id' AND charname = '$user_charname'";
$user_glyph_id = 0;
if(!mysqli_query($conn, $stmt3)) $transaction_ok = false;
if(!mysqli_query($conn, $stmt4)) $transaction_ok = false;
if(!($result = mysqli_query($conn, $stmt5)) ) 
    $transaction_ok = false;
else 
    $user_glyph_id = mysqli_fetch_array($result, MYSQLI_ASSOC)['glyph_id'];

if(!$transaction_ok) {
    mysqli_rollback($conn); 
    exit_with_error('DB operation error at updating font and glyph states'); 
}

//Step 3: Prepare glyph image and store to disk
if ($user_image->content_type != "image/jpeg") exit_with_error('not a JPEG image');
$path = '/home/ubuntu/AlyssaData/Users/'.$user_id.'/'.$user_fontname.'/'.$user_glyph_id.'.glyph';
$user_image_data = base64_decode( str_replace(' ', '+', $user_image->file_data) );

if(!file_put_contents($path, $user_image_data)){
    mysqli_rollback($conn); 
    exit_with_error('DB operation error at updating font and glyph states'); 
} 

//TODO:
//Step 4: Create new font file on disk

//If something goes wrong, rollback transaction and call exit_with_error()

//If everything is OK so far
mysqli_commit($conn);
$return_data = array("success"=>true, "message" =>'glyph updated successfully');
echo json_encode($return_data);
?>
