<?php 
error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'waregistration.php';
require_once 'wahelper.php';

$rawData = file_get_contents('php://input');
loadPostParams($rawData);

handleIncomingWhatsAppRequest();

?>