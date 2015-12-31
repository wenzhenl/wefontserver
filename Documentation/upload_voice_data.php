<?php
// get json object from sender
$json = file_get_contents('php://input');
$obj = json_decode($json);

// configure directory perssion
$dirname = "/home/wenzheng/VoiceCal/voices/".time();
$oldmask = umask(0);
mkdir($dirname, 0777);
umask($oldmask);

// example of parsing json
$count = 1;
foreach ($obj->voices as $voice) {
  if($voice->content_type == "multipart/form-data"){
    $filename = $count . ".wav";
    $target_file = "$dirname/$filename";
    $data = str_replace(' ', '+', $voice->file_data);
    $data = base64_decode($data);
    file_put_contents($target_file, $data);
    chmod($target_file, 0777);
    chgrp($target_file, "wenzheng");
    $count += 1;
  }
}

shell_exec('/home/wenzheng/VoiceCal/voices/voice2T.sh '.$dirname);

// save file into local system
$result_json = file_get_contents("$dirname/out.json");
$result = json_decode($result_json, true);

$result_audio = "$dirname/result.wav";
$result_audio_file = fopen($result_audio, 'r');
$result_audio_data = fread($result_audio_file, filesize($result_audio));

if($result['valid'] == true) {
  $return_data = array("success"=>true, "message"=>"The voice has been uploaded.",
    "valid"=>true,
    "expression"=>$result['expression'], "result"=>$result['result'],
    "audio"=>base64_encode($result_audio_data));
} else {
  $return_data = array("success"=>true, "message"=>$result['errorInfo'],
    "valid"=>false,
    "errorInfo"=>$result['errorInfo'],
    // this is how send data back to server
    "audio"=>base64_encode($result_audio_data));
}
echo json_encode($return_data);
?>
