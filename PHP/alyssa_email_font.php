<?php
require_once('alyssa_common_helper.php');
require_once('vendor/autoload.php');

//*************** PHP script starts here ************

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email    = mysqli_real_escape_string($conn, $jobj->email);
$user_password = mysqli_real_escape_string($conn, $jobj->password);
$user_fontname = mysqli_real_escape_string($conn, $jobj->fontname);
if (empty($user_email) || empty($user_password) || empty(user_fontname)) 
    exit_with_error('JSON object error');

$row = verify_user_password($conn, $user_email, $user_password);
$user_id = $row['user_id'];


/***************** Prepare Fontfile to Send ****************/
$stmt = "SELECT * FROM Font WHERE user_id = '$user_id' AND fontname = '$user_fontname'";
$result = mysqli_query($conn, $stmt);
if(!$result) exit_with_error('DB op failure: SELECT');
if(mysqli_num_rows($result) == 0) exit_with_error('requested font not found');
$font_id = mysqli_fetch_array($result, MYSQLI_ASSOC)['font_id'];
$fontfile_path = ALYSSA_USER_PATH.'/'.$user_id.'/'.$font_id.'/'.ALYSSA_DEFAULT_FONTFILE;

/************** Send Email to User with Attachment *********/
$mail = new PHPMailer;
$mail->CharSet = 'UTF-8';

$email_subject = "Alyssa Font";
$email_msg = "Dear Alyssa App user:\n\nWe have sent you the font file in the email attachment.\n".
    "Please download the file and enjoy!\n\nAlyssa Support Team";

//Send mail using gmail as the SMTP server
$mail->IsSMTP();
$mail->SMTPAuth   = true;
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;
$mail->Username   = 'alyssaappteam@gmail.com';
$mail->Password   = 'alyssa2016ok';
$mail->Subject    = $email_subject;
$mail->Body       = $email_msg;

$mail->isHTML(false);
$mail->setFrom('alyssaappteam@gmail.com', 'Alyssa Support Team');
$mail->addAddress($user_email);
$mail->AddAttachment($fontfile_path, $fontname.'.ttf');

if(!$mail->send()){
    exit_with_error('failed to send email: '.$mail->ErrorInfo);
} else {
    $return_data = array("success"=>true, "message" =>'font file sent to'.$user_email);
    echo json_encode($return_data);
}

?>
