<?php
require_once __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

// twilio constants
$twilioAppId = "<to be filled>";
$twilioAppSecret = "<to be filled>";
$whatsAppBusinessNumber = "<to be filled | starts with countrycodefollowedbycompletenumber | no spaces and special character>";
$twilioClient = new Client($twilioAppId, $twilioAppSecret);

function loadPostParams($rawData)
{
    global $postParams;
    $postArr = explode("&", $rawData);
    foreach ($postArr as $string)
    {   
        $postSubArr = explode("=", $string);
        if (!empty($postSubArr))
        {
            $postParams[$postSubArr[0]] = html_entity_decode($postSubArr[1]);
            $postSubArr = array();
        }
    }
}

function getWhatsAppMessageBody() 
{
    global $postParams;
    $data = trim($postParams["Body"]);
    return $data;
}

// will work as unique session id
function getSenderWhatsAppNumber() 
{
    global $postParams;
    $data = substr($postParams["WaId"], 2);
    return $data;
}

function getSenderWhatsAppNumberWithFormat() 
{
    global $postParams;
    $data = $postParams["WaId"];
    return "whatsapp:+" . $data;
}

function getBusinessWhatsAppNumberWithFormat() 
{
    global $whatsAppBusinessNumber;
    return "whatsapp:+" . $whatsAppBusinessNumber;
}

function hasWhatsAppMedia() 
{
    global $postParams;
    if ($postParams["NumMedia"])
    {
        return true;
    } 
    else
    {
        return false;
    }
}

function getWhatsAppMediaUrl()
{
    global $postParams;
    return $postParams["MediaUrl0"];
}

function getWAImage()
{
    global $postParams;
    $allowedImageTypes = array('image/jpeg','image/jpg','image/gif','image/png');
    $profileImg = '';

    if ($postParams["NumMedia"])
    {
        $decodedContentType = urldecode($postParams["MediaContentType0"]);
        if (in_array($decodedContentType, $allowedImageTypes))
        {
            if ($postParams["MediaUrl0"])
            {
                $imageUrl = urldecode($postParams["MediaUrl0"]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $imageUrl);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $a = curl_exec($ch); // $a will contain all headers

                // This is what you need, it will return you the last effective URL
                $profileImg = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); 
            }
        }
    }

    return $profileImg;
}

function replyToSenderWhatsAppBasicText($response)
{
//    echo $response;
    global $twilioClient;
    $twilioClient->messages
                 ->create(getSenderWhatsAppNumberWithFormat(), // to
             [
                 "from" => getBusinessWhatsAppNumberWithFormat(),
                 "body" => $response
             ]
    );
}

function replyToSenderWhatsAppBasicTextFromAPI($from, $to, $message)
{
    global $twilioClient;
    $twilioClient->messages
                 ->create($to, // to
             [
                 "from" => $from,
                 "body" => $message
             ]
    );    
}

function replyToSenderWhatsAppMediaFromAPI($from, $to, $message, $mediaUrl)
{
    global $twilioClient;
    $twilioClient->messages
                 ->create($to, // to
             [
                "mediaUrl" => [$mediaUrl],
                 "from" => $from,
                 "body" => $message
             ]
    );
}
?>