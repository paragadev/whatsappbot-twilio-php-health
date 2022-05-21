<?php

use Twilio\TwiML\Messaging\Message;

require 'dataadapter.php';
require_once 'messageresources.php';
require_once 'wahelper.php';

//bot commands
$activationCmd = "hi";
$helpCmd = "help";
$cancelCmd = "cancel";

$sessionTimeoutInMins = 15;

// whatsApp workflow steps
$WELCOME_STEP = 0;

// required for registration.
$FULLNAME_STEP = 1;
$AGE_STEP = 2;
$GENDER_STEP = 3;

// then present the menu, asking 1. consult doctor now, 2. book appt later 3. book video appt etc..
$MAIN_MENU_STEP = 4;
$SUB_MENU_STEP = 5;

// present available departments by hid 
$SPECIALITY_STEP = 6;

// present available doctors by hid and selected department
$DOCTOR_STEP = 7;

// last question before appt booking, ask the chief compliant.
$SYMPTOM_STEP = 8;

$CONFIRMATION_STEP = 9;
$ONLINE_PAYMENT_STEP = 10;

// additional steps
$PROFILE_PIC_STEP = 21;
$PROFILE_PIC_STEP_UPDATE = 22;
$BOOK_LAB_TEST_STEP = 23;

$postParams = array();

$waDataAdapter = WADataProvider::instance();

// debugging
$EXECUTION_TIME_DEBUG = false;

//returns false if format or clinic code is not valid. returns hospital id, if valid.
function checkIfValidActivationMessageWithRightClinicCode($userQuery)
{
    global $activationCmd;
    global $waDataAdapter;

    if ($userQuery) {
        // remove double spaces, tabs and convert them into single space.
        $userQuery = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $userQuery);

        if (strpos($userQuery, " ") !== false) {
            // it should result in array with two values. First one should be hi followed by hospital/clinic numeric code.
            $pieces = explode(" ", $userQuery);

            if (count($pieces) == 2) {
                if ($pieces[0] == "🙏" || strcasecmp($pieces[0], $activationCmd) == 0) {
                    $hospDetails = $waDataAdapter->getHospitalDetails(strtolower($pieces[1])); 
                    if ($hospDetails) {
                            return $hospDetails;
                    }
                }
            }
        }
    }

    return false;
}

function checkSpecialCommandsAndInactivity(WARegistration $waReg)
{
    global $waDataAdapter;
    global $MAIN_MENU_STEP;
    global $sessionTimeoutInMins;

    $incomingWhatsAppMessage = trim(strtolower(urldecode(getWhatsAppMessageBody())));
    $response = '';

    switch ($incomingWhatsAppMessage)
    {
        case "menu" : 
            $tmp = new WARegistration();
            $tmp->id = $waReg->id;
            $tmp->current_step = $MAIN_MENU_STEP;
            $waDataAdapter->updateInProgressRegistrationDetails($tmp);

            $patientIdMessage = '';
            $patientIdMessage = getLocalizedMessage(MessageKeys::$ExistingPatientId) .  "*" . $waReg->patient_id . "*";             

            $hospDetails = $waDataAdapter->getHospitalDetails($waReg->hid_code);
            $response = $hospDetails['url'] . "\n" . "*" . $hospDetails['desc'] . "*" . "\n\n" . getLocalizedMessage(MessageKeys::$NameGreeting) . $waReg->name . "\n" . 
            $patientIdMessage . "\n\n" . getLocalizedMessage(MessageKeys::$MainMenu); 
            break;

        case "exit" :
            $waDataAdapter->markWorkflowComplete($waReg->id);
            $response = str_replace("{0}", $waReg->hid_code, getLocalizedMessage(MessageKeys::$SessionExit));
            break;            
    }

    //Now check the inactivity
    if ($waReg->last_updated)
    {
        $currentTime = new DateTime();
        $lastUpdateTime = new DateTime($waReg->last_updated);
        $diff = date_diff($currentTime, $lastUpdateTime);
        if ($diff->i && $diff->i > 0)
        {
            if ($diff->i >= $sessionTimeoutInMins)
            {
                //$hospDetails = $waDataAdapter->getHospitalDetails($waReg->hid_code);
                $response = str_replace("{0}", $waReg->hid_code, getLocalizedMessage(MessageKeys::$SessionExpiry));
                $waDataAdapter->markWorkflowComplete($waReg->id);
            }
        }
    }

    return $response;
}

/* conditions:
1. if workflow has not started and user has not sent right activation message - 
don't do anything and return message back to user to mention correct activation message.
2. if workflow has not started but user has sent right activation message -  
create an entry in the table, get whatsapp#, call EWS to check if this is an existing patient. 
-- if it is an existing patient, display record details (name, phone, gender, dob/age), set step = 5 to ask speciality and symptom.
-- if it is not an existing patient, set step = 0, and start asking details from name till symptoms.
*/
function handleIncomingWhatsAppRequest()
{
    global $EXECUTION_TIME_DEBUG;
    $startTime = new DateTime();

    global $waDataAdapter;
    $response = '';
    $currentWorkflowStep = -1;

    $userWhatsappNumber = getSenderWhatsAppNumber();
    $waRegObj = $waDataAdapter->getInProgressRegistrationDetails($userWhatsappNumber);
    if ($waRegObj)
    {
        $currentWorkflowStep = (int)$waRegObj->current_step;
    }

    $incomingWhatsAppMessage = trim(urldecode(getWhatsAppMessageBody()));

    //bot commands
    global $activationCmd;
    global $helpCmd;
    global $cancelCmd;

    // whatsApp workflow steps
    global $FULLNAME_STEP;
    global $AGE_STEP;
    global $GENDER_STEP;
    global $SPECIALITY_STEP;
    global $SYMPTOM_STEP;
    global $CONFIRMATION_STEP;
    global $ONLINE_PAYMENT_STEP;
    global $MAIN_MENU_STEP;
    global $DOCTOR_STEP;
    global $SUB_MENU_STEP;
    global $PROFILE_PIC_STEP;
    global $PROFILE_PIC_STEP_UPDATE;
    global $BOOK_LAB_TEST_STEP;

    if ($currentWorkflowStep < 0) 
    {
        $hospDetails = checkIfValidActivationMessageWithRightClinicCode($incomingWhatsAppMessage);
        if ($hospDetails) {
            // now first make a web service call to check if whatsapp phone is registered or not.
            // if yes, show personal details and mark the step directly to 5, else start with step = 1 by asking full name. 
            // API - getdetails(whatsapp#) return false | {}
            $checkUserResult = $waDataAdapter->checkIfUserAlreadyRegistered($userWhatsappNumber);

            if (!$checkUserResult) // new user
            {
                $lastId = $waDataAdapter->addFirstTimeRegistration($userWhatsappNumber, $hospDetails['hid'], $hospDetails['code'], $FULLNAME_STEP, '', '', '', '');
                $response = $hospDetails['url'] . "\n" . $hospDetails['desc'] . "\n\n" . getLocalizedMessage(MessageKeys::$FullName); 
            }
            else // existing user
            {
                $patientIdMessage = '';
                if ($checkUserResult->patient_id) {
                    $patientIdMessage = getLocalizedMessage(MessageKeys::$ExistingPatientId) .  "*" . $checkUserResult->patient_id . "*";             
                }

                $response = $hospDetails['url'] . "\n" . "*" . $hospDetails['desc'] . "*" . "\n\n" . getLocalizedMessage(MessageKeys::$NameGreeting) . $checkUserResult->name . "\n" . 
                $patientIdMessage . "\n\n" . getLocalizedMessage(MessageKeys::$MainMenu);                
               // $response = $hospDetails['url'] . "\n" . getLocalizedMessage(MessageKeys::$NameGreeting) . $checkUserResult->name . ", " . $hospDetails['desc'] . "\n" .
                 //           $patientIdMessage .  
                   //         "\n\n" . getLocalizedMessage(MessageKeys::$MainMenu); 
                
                $lastId = $waDataAdapter->addFirstTimeRegistration($userWhatsappNumber, $hospDetails['hid'], $hospDetails['code'], $MAIN_MENU_STEP, $checkUserResult->name, $checkUserResult->age, $checkUserResult->gender, $checkUserResult->patient_id);
            }
        }
        else // user has not send correct activation message or right clinic code.
        {
            $response = getLocalizedMessage(MessageKeys::$ReaffirmWelcome);
        }
    }
    else 
    {
        $response = checkSpecialCommandsAndInactivity($waRegObj);
        if ($response)
        {
            replyToSenderWhatsAppBasicText($response);
            exit;
        }    

        switch ($currentWorkflowStep) {
            case $FULLNAME_STEP:
                if (checkNameInput($incomingWhatsAppMessage))
                {
                    $response = getLocalizedMessage(MessageKeys::$Age);
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->name = $incomingWhatsAppMessage;
                    $tmp->current_step = $AGE_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectInput);
                }

                break;

            case $AGE_STEP:
                if (checkAgeInput($incomingWhatsAppMessage))
                {
                    $response = getLocalizedMessage(MessageKeys::$Gender);
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->age = $incomingWhatsAppMessage;
                    $tmp->current_step = $GENDER_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectInput);
                }

                break;

            case $GENDER_STEP:
                if (checkGenderInput($incomingWhatsAppMessage))
                {
                    $response = getLocalizedMessage(MessageKeys::$ProfilePic);
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->gender = $incomingWhatsAppMessage;
                    $tmp->current_step = $PROFILE_PIC_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectInput);
                }

                break;

            case $PROFILE_PIC_STEP :
                $pp = getWAImage();
                if ($pp)
                {
                    $response = '';
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->profile_pic = $pp;
                    $tmp->current_step = $MAIN_MENU_STEP;

                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);

                    $tmp->name = $waRegObj->name;
                    $tmp->age = $waRegObj->age;
                    $tmp->hid = $waRegObj->hid;
                    $tmp->gender = $waRegObj->gender;

                    // register patient.
                    $regObj = $waDataAdapter->registerPatient($tmp);
                    if ($regObj)
                    {
                        $regSuccessResponse = getLocalizedMessage(MessageKeys::$RegistrationComplete) . "*" . $regObj->patient_id . "*";
                        $response = $regSuccessResponse . "\n\n" . getLocalizedMessage(MessageKeys::$MainMenu);

                        // call API to update patient profile pic, once patient registration is successful.
                        $waDataAdapter->updatePatientProfilePic($regObj->patient_id, $pp); 
                    }
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$InvalidProfilePic);
                }
                break;

            case $PROFILE_PIC_STEP_UPDATE :
                $pp = getWAImage();
                if ($pp)
                {
                    $response = '';
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->profile_pic = $pp;
                    $tmp->current_step = $CONFIRMATION_STEP;
                    $tmp->workflow_completed = 1;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);

                    $response = getLocalizedMessage(MessageKeys::$ProfilePicUpdateSuccessful);

                    // call API to update patient profile pic, once patient registration is successful.
                    $waDataAdapter->updatePatientProfilePic($waRegObj->patient_id, $pp); 
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$InvalidProfilePic);
                }
                break; 

            case $MAIN_MENU_STEP:
                if (checkIfCorrectMainMenuOptionPressed($incomingWhatsAppMessage))
                {
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->main_menu_option = $incomingWhatsAppMessage;

                    if ($tmp->main_menu_option == '1' || $tmp->main_menu_option == '2')
                    {
                        $tmp->current_step = $SUB_MENU_STEP;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);  
                        $response = getLocalizedMessage(MessageKeys::$SubMenuAvailabilty); 
                    }
                    
                    // handle view upcoming appts
                    if ($tmp->main_menu_option == '3')
                    {
                        $response = $waDataAdapter->viewUpcomingAppointments($userWhatsappNumber, $waRegObj->hid); 
                        if (!$response)
                        {
                            $hospDetails = $waDataAdapter->getHospitalDetails($waRegObj->hid_code);
                            $response = str_replace("{0}", $hospDetails['code'], getLocalizedMessage(MessageKeys::$NoUpcomingAppt));
                        }

                        $tmp->current_step = $CONFIRMATION_STEP;
                        $tmp->workflow_completed = 1;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    }

                    // get records, pres
                    if ($tmp->main_menu_option == '4')
                    {
                        $response = $waDataAdapter->getHealthRecords($waRegObj->patient_id); 
                        if (!$response)
                        {
                            $response = getLocalizedMessage(MessageKeys::$NoHealthRecords);
                        }

                        $tmp->current_step = $CONFIRMATION_STEP;
                        $tmp->workflow_completed = 1;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    }
                    
                    // profile pic update
                    if ($tmp->main_menu_option == '5')
                    {
                        $response = getLocalizedMessage(MessageKeys::$ProfilePicUpdate);
                        $tmp->current_step = $PROFILE_PIC_STEP_UPDATE;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    }

                    // book a lab test
                    if ($tmp->main_menu_option == '10')
                    {
                        $response = getLocalizedMessage(MessageKeys::$BookALabTest);
                        $tmp->current_step = $CONFIRMATION_STEP;
                        $tmp->workflow_completed = 1;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    }    
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectOption);
                } 
                break;       

            case $SUB_MENU_STEP:
                if (checkIfCorrectSubMenuOptionPressed($incomingWhatsAppMessage))
                {
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->sub_menu_option = $incomingWhatsAppMessage;                    
                    $tmp->current_step = $SPECIALITY_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);

                    $waRegObj->sub_menu_option = $incomingWhatsAppMessage;
                    $waRegObj->current_step = $SPECIALITY_STEP;

                    $wsresponse = $waDataAdapter->getDepartments($waRegObj);
                    if ($wsresponse)
                    {
                        $response = getLocalizedMessage(MessageKeys::$SpecialitySelection) . "\n" . $wsresponse;
                    }
                    else
                    {
                        $hospDetails = $waDataAdapter->getHospitalDetails($waRegObj->hid_code);
                        $rspEmgNo = str_replace("{0}", $hospDetails['helpline'], getLocalizedMessage(MessageKeys::$ServicesNotAvailable));
                        $response = str_replace("{1}", $hospDetails['code'], $rspEmgNo);

                        // reset the workflow
                        $waDataAdapter->markWorkflowComplete($waRegObj->id);                        
                    }
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectOption);
                }                
                break;
                
            case $SPECIALITY_STEP:
                if (checkIfCorrectSpecialityOptionPressed($incomingWhatsAppMessage))
                {
                    $response = '';
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->whatsapp_number = $userWhatsappNumber;
                    $tmp->speciality = $incomingWhatsAppMessage;
                    $tmp->current_step = $DOCTOR_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);

                    $waRegObj->speciality = $incomingWhatsAppMessage;
                    $waRegObj->current = $DOCTOR_STEP;    
                    $wsresponse = $waDataAdapter->getDoctors($waRegObj);
                    if ($wsresponse)
                    {
                        $response = getLocalizedMessage(MessageKeys::$DoctorSelection) . "\n" . $wsresponse
                                . "\n\n" . getLocalizedMessage(MessageKeys::$GoBackPress0); 
                    }
                    else
                    {
                        $hospDetails = $waDataAdapter->getHospitalDetails($waRegObj->hid_code);
                        $rspEmgNo = str_replace("{0}", $hospDetails['helpline'], getLocalizedMessage(MessageKeys::$ServicesNotAvailable));
                        $response = str_replace("{1}", $hospDetails['code'], $rspEmgNo);

                        // reset the workflow
                        $waDataAdapter->markWorkflowComplete($waRegObj->id);
                    }
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectOption);
                }
                break;

            case $DOCTOR_STEP:
                if (checkIfCorrectDoctorOptionPressed($incomingWhatsAppMessage))
                {
                    if (checkIfBackOptionPressed($incomingWhatsAppMessage))
                    {
                        $tmp = new WARegistration();
                        $tmp->id = $waRegObj->id;
                        $tmp->current_step = $SPECIALITY_STEP;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                        
                        $waRegObj->current_step = $SPECIALITY_STEP;
                        $response = getLocalizedMessage(MessageKeys::$SpecialitySelection) . "\n" . $waDataAdapter->getDepartments($waRegObj);
                    }
                    else 
                    {
                        $response = getLocalizedMessage(MessageKeys::$Symptom) . "\n\n" . getLocalizedMessage(MessageKeys::$GoBackPress0);
                        $tmp = new WARegistration();
                        $tmp->id = $waRegObj->id;
                        $tmp->whatsapp_number = $userWhatsappNumber;
                        $tmp->doctor = $incomingWhatsAppMessage;
                        $tmp->current_step = $SYMPTOM_STEP;
                        $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    }
                }
                else
                {
                    $response = getLocalizedMessage(MessageKeys::$IncorrectOption);
                }
                break;

            case $SYMPTOM_STEP:
                if (checkIfBackOptionPressed($incomingWhatsAppMessage))
                {
                    $tmp = new WARegistration();
                    $tmp->id = $waRegObj->id;
                    $tmp->current_step = $DOCTOR_STEP;
                    $waDataAdapter->updateInProgressRegistrationDetails($tmp);
                    
                    $waRegObj->current_step = $DOCTOR_STEP;
                    $response = getLocalizedMessage(MessageKeys::$DoctorSelection) . "\n" . $waDataAdapter->getDoctors($waRegObj)
                                . "\n\n" . getLocalizedMessage(MessageKeys::$GoBackPress0);                    
                }
                else
                {
                    $waRegObj->symptom = $incomingWhatsAppMessage;
                    $waRegObj->current_step = $CONFIRMATION_STEP;

                    replyToSenderWhatsAppBasicText(getLocalizedMessage(MessageKeys::$ApptBookingStart));
                    $wsresponse = $waDataAdapter->bookAppointment($waRegObj);

                    if ($wsresponse)
                    {
                        $response = $wsresponse;  
                    }
                    else
                    {
                        $hospDetails = $waDataAdapter->getHospitalDetails($waRegObj->hid_code);
                        $rspEmgNo = str_replace("{0}", $hospDetails['helpline'], getLocalizedMessage(MessageKeys::$ServicesNotAvailable));
                        $response = str_replace("{1}", $hospDetails['code'], $rspEmgNo);
                    }
                }
                break;
    
            case $CONFIRMATION_STEP:
                $response = getLocalizedMessage(MessageKeys::$InPogressTransaction);
                break;
        }
    }

    if ($EXECUTION_TIME_DEBUG)
    {
        $endTime = new DateTime();
        $diff = date_diff($startTime, $endTime);
        $response = $response . "\n" . '*Execution Time (sec): ' . $diff->s . "*";
    }

    replyToSenderWhatsAppBasicText($response);
}

function checkAgeInput($age)
{
    if (is_numeric($age) && strpos($age, ".") == false)
    {
        $age = (int)$age;
        if ($age >= 5 && $age <= 100)
        {
            return true;
        }
        return false;
    }
    return false;
}

function checkGenderInput($gender)
{
    if ($gender == '1' || $gender == '2' || $gender == '3')
    {
        return true;
    }

    return false;
}

function checkNameInput($name)
{
    return true;
}

function checkIfCorrectMainMenuOptionPressed($mainMenuOption)
{
    global $mainMenuMessageArrEnglish;
    if (array_key_exists($mainMenuOption, $mainMenuMessageArrEnglish))
    {
        return true;
    }
    return false;
}

function checkIfCorrectSubMenuOptionPressed($subMenuOption)
{
    if ($subMenuOption == '1' || $subMenuOption == '2' || $subMenuOption == '3')
    {
        return true;
    }
    return false;
}

function checkIfCorrectSpecialityOptionPressed($specialityOption)
{
    if (is_numeric($specialityOption) && strpos($specialityOption, ".") == false)
    {
        return true;
    }
    return false;
}

function checkIfCorrectDoctorOptionPressed($doctorOption)
{
    if (is_numeric($doctorOption) && strpos($doctorOption, ".") == false)
    {
        return true;
    }
    return false;
}

function checkIfBackOptionPressed($backoption)
{
    if ($backoption == '0')
    {
        return true;
    }
    return false;
}
?>