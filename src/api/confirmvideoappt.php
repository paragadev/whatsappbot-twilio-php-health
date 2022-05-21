<?php
require_once '../wahelper.php';
require_once '../dataadapter.php';
require_once '../messageresources.php';

header('Access-Control-Allow-Origin: *');
header('Content-type: application/json');

$rawData = file_get_contents('php://input');
$obj = json_decode($rawData);
if ($obj)
{
    if ($obj->appt_id)
    {
        $waDP = WADataProvider::instance();
        $waDP->confirmVideoAppt($obj);

        // prepare and send response 
        $from = getBusinessWhatsAppNumberWithFormat();
        $to = "whatsapp:+91" . $obj->mobile;

        $waReg = $waDP->getInProgressRegistrationDetails($obj->mobile);

        $confirmationSuccessMessage = getLocalizedMessage(MessageKeys::$VdoApptBookingConfirmation);
        $confirmationSuccessMessage = str_replace("{appt_id}", $obj->appt_id, $confirmationSuccessMessage);
        $confirmationSuccessMessage = str_replace("{doctor}", $obj->doctor, $confirmationSuccessMessage);
        $confirmationSuccessMessage = str_replace("{time}", $obj->appdatetime, $confirmationSuccessMessage);
        $confirmationSuccessMessage = str_replace("{link}", $obj->link, $confirmationSuccessMessage);
        //replyToSenderWhatsAppBasicTextFromAPI($from, $to, $confirmationSuccessMessage);

        $thankyou = str_replace("{0}", $waReg->hid_code, getLocalizedMessage(MessageKeys::$ThankYouServices));  

        $confirmationSuccessMessage = $confirmationSuccessMessage . "\n\n" . $thankyou;
        replyToSenderWhatsAppBasicTextFromAPI($from, $to, $confirmationSuccessMessage);

        echo '{"status":"1"}'; 
    }
    else
    {
        echo '{"status":"0"}';
    } 
}
else
{
    echo '{"status":"0"}'; 
}

?>