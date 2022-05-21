<?php
require_once '../wahelper.php';
require_once '../messageresources.php';

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

$rawData = file_get_contents('php://input');
$obj = json_decode($rawData);
// {mobile:<>, mediapath:<url to image, audio, video>, message:<>, calltoaction:<>}

if ($obj)
{
    $from = getBusinessWhatsAppNumberWithFormat();
    $to = "whatsapp:+91" . $obj->mobile;
    $response = $obj->message;
    if ($obj->calltoaction)
    {
        $response = $response . "\n\n" . $obj->calltoaction; 
    }

    if ($obj->mediapath)
    {
        replyToSenderWhatsAppMediaFromAPI($from, $to, $response, $obj->mediapath);
    }
    else
    {
        replyToSenderWhatsAppBasicTextFromAPI($from, $to, $response);
    }
    echo '{"status":"1"}'; 
}
else
{
    echo '{"status":"0"}';
} 
?>