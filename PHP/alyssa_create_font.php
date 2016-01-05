<?php
require_once('alyssa_common_helper.php');

//Creates the .ttf font file under $font_path
//Returns true if this operation is sucessful
//Returns false otherwise
function create_font_file($font_path, $fontname, $copyright, $version){
    $font_fname = $font_path.'/alyssafont.ttf';
    $output = shell_exec(ALYSSA_SCRIPT_PATH."/initializeFontFile.sh \"$font_fname\"  \"$fontname\" \"$copyright\" \"$version\"");

    if(is_null($output)) return false;
    else return true;
}

/*Performs 3 tasks
 * 1. Insert new font into DB
 * 2. Create font directory
 * 3. Create font file (.ttf)
 * Returns true if all operations are successful
 * Rolls back DB and terminates script if anything goes wrong
 */
function add_new_font($conn, $user_id, $fontname, $copyright, $version, $base_path) {
    $stmt = "INSERT INTO Font VALUES (NULL, '$user_id', '$fontname', '$copyright', '$version', NULL, NOW(), TRUE) ";
    if(mysqli_query($conn, $stmt)) {
        $stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND fontname = '$fontname' ";
        if(!($result = mysqli_query($conn, $stmt))) 
            rollback_and_exit($conn, 'DB op failure: SELECT');

        $font_row  = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $font_id   = $font_row['font_id'];
        $copyright = $font_row['copyright'];
        $version   = $font_row['version'];

        $font_path = $base_path.'/'.$user_id.'/'.$font_id;
        if(mkdir($font_path, 0777, true)) { //create font dir
            $err_msg = '';
            if (create_font_file($font_path, $fontname, $copyright, $version)){
               return true; 
            } else {
                rollback_and_exit($conn, 'failed to create the font fail'); 
            } 
        }else{
            rollback_and_exit($conn, 'failed to create the font directory'); 
        }
    } else {
        rollback_and_exit($conn, 'DB op failure: cannot insert font');
    }
}


/**************** Script Starts Here **************/

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
$copyright     = mysqli_real_escape_string($conn, $jobj->copyright);
$version       = mysqli_real_escape_string($conn, $jobj->version);

if (empty($user_email) || empty($user_password) || empty($user_fontname) || empty($copyright) || empty($version) )
    exit_with_error('JSON object error');    

$row     = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];

$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' ";
$result = exec_query ($conn, $stmt); //contains all fonts of this user


/* This single TRANSACTION performs:
 * 1. Inactivate previous active font 
 * 2. Insert new font into DB
 * 3. Create font dir AND font file for the new font
 * */
mysqli_autocommit($conn, false);
$transaction_ok = true;

if (mysqli_num_rows($result) == 0){//User has no font at all

    add_new_font($conn, $user_id, $user_fontname, $copyright, $version, ALYSSA_USER_PATH);

} else {//User has some font under his account

    //Check if fontname exists already
    $stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname'";
    $result = mysqli_query($conn, $stmt);
    if(!$result) rollback_and_exit($conn, 'DB op failure: SELECT');
    if(mysqli_num_rows($result) != 0) rollback_and_exit($conn, 'same fontname already exists');

    //Inactivate previous active font
    $stmt  = 'UPDATE Font SET font_active = FALSE WHERE '.
        "user_id = '$user_id' AND font_active IS TRUE";
    if(!mysqli_query($conn, $stmt)) rollback_and_exit($conn, 'DB op failure: UDATE');

    add_new_font($conn, $user_id, $user_fontname, $copyright, $version, ALYSSA_USER_PATH);
} 

//Finally, everything is OK, commit the DB operations and return
mysqli_commit($conn);

$return_data = array("success"=>true, "message" =>'font updated successfully');
echo json_encode($return_data);

?>
