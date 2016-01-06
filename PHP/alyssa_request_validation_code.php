<?php
require_once('alyssa_common_helper.php');
require_once('vendor/autoload.php');

function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;
}

function send_vc_email($user_email, $vc, &$err_info){
    //send the validation code to email
    $email_subject = "Alyssa Password Reset Validation Code";
    $email_msg = "Dear Alyssa App user:\n\nYour Validation Code is: $vc\n".
        'This code expires in 20 minutes, please use it to reset your password '.
        "as soon as possible.\n\nAlyssa Support Team";

    $mail = new PHPMailer;
    $mail->CharSet = 'UTF-8';
    // $mail->SMTPDebug = 2; //uncomment this line to debug SMTP

    //Send mail using gmail
    $send_using_gmail = true;
    if($send_using_gmail){//use gmail's free SMTP service
        $mail->IsSMTP(); //use SMTP 
        $mail->SMTPAuth = true; // enable SMTP authentication
        $mail->Host = 'smtp.gmail.com'; // use GMAIL as the SMTP server
        // $mail->SMTPSecure = 'ssl'; // If ssl, use Port 465
        // $mail->Port = 465; // 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587; 
        $mail->Username = 'alyssaappteam@gmail.com'; // GMAIL username
        $mail->Password = 'alyssa2016ok'; // GMAIL password
    }

    $mail->setFrom('alyssaappteam@gmail.com', 'Alyssa Support Team');
    $mail->addAddress($user_email);
    $mail->isHTML(false);
    $mail->Subject = $email_subject;
    $mail->Body = $email_msg;

    if(!$mail->send()){
        $err_info = $mail->ErrorInfo;
        return false;
    } else {
        return true;
    }
}

//*************** PHP script starts here ************

$conn = connect_AlyssaDB();

$json = file_get_contents('php://input');
$jobj = json_decode($json);
$user_email = mysqli_real_escape_string($conn, trim($jobj->email));
if (empty($user_email)) exit_with_error('JSON object error');

//Validation code is 6-digit random number
$vc = randString(6, '0123456789');
$vc_encoded = password_hash($vc, PASSWORD_DEFAULT);
$stmt = "SELECT * FROM UserValidation WHERE vc_email = '$user_email'";
$result = exec_query($conn, $stmt);

if (mysqli_num_rows($result) == 0) {//First time a user tries to reset psw
    $stmt = "SELECT * FROM User WHERE user_email = '$user_email'";
    $result = exec_query($conn, $stmt);
    if (mysqli_num_rows($result) == 0) 
        exit_with_error('user does not exit with email '.$user_email);

    $stmt = 'INSERT INTO UserValidation (vc_email, validation_code) '.
        "VALUES ('$user_email', '$vc_encoded')";
    exec_query($conn, $stmt);
} else {
    //Update validation code
    $stmt = "Update UserValidation SET validation_code = '$vc_encoded' ".
        " WHERE vc_email = '$user_email' ";
    exec_query($conn, $stmt);
}

$err_info = '';
if (send_vc_email($user_email, $vc, $err_info)){
    $return_data = array("success"=>true, "message" =>'user validation code sent to '.$user_email);
    echo json_encode($return_data);
} else {
    exit_with_error('failed to send vc to user email '.$user_email.' ErrorInfo: '.$err_info);
}

?>
