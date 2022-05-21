<?php

// english dictionary
$em = [];
$em["name-greeting"] = "🙏 ";
$em["welcome"] = "Please type *hi hospital-code* to begin";
$em["reaffirm-welcome"] = "Please type *hi* with correct hospital code to begin";

// registration messages
$em["fullname"] = "Looks like you are not registered.\n\n*Please send your full name*";
$em["age"] = "*Please send your age*";
$em["gender"] = "*Please send your gender*\n Type *1* for Male🤵🏻‍♂️ \n Type *2* for Female🤵🏻‍♀️";
$em["profile-pic"] = "*Please attach your profile picture to complete the registration process.*\n\n*You can also take a selfie!*🤳" . "\n\n" 
                    . "_(Image size should not exceed 5 MB)_"; 
$em["profile-pic-update"] = "*Please attach your profile picture to update.*\n\n*You can also take a selfie!*🤳" . "\n\n" 
                    . "_(Image size should not exceed 5 MB)_"; 
$em["invalid-profile-pic"] = "*Please provide correct input to proceed*" . "\n" . 
                             "1. Image file is invalid or type is not allowed. Allowed types: _.jpeg,.jpg,.gif,.png_" . "\n" .
                             "2. File size should not exceed _5 MB_";
$em["profile-pic-update-success"] = "Dear customer, your profile picture is successfully updated in our records!";

$mainMenuMessageArrEnglish = array("1" => "Consult Doctor", 
                            "2" => "Book Video Appointment",
                            "3" => "View Upcoming Appointment",
                            "4" => "Get My Health Records",
                            "5" => "Update Profile Picture"
                            //"10" => "Book a Lab Test"
                            );

$em["main-menu"] = "*How can we help you today?*\n\n" . constructMainMenu($mainMenuMessageArrEnglish) . "\n" .
                   "_(You can always type *menu* to return to main menu and *exit* to close this conversation)_";

$em["speciality-selection"] = "*Please select Department*";
$em["doctor-selection"] = "*Please select Doctor*🩺";
$em["symptom"] = "*What are your Chief Complaints?*";
$em["registration-complete"] = "Congratulations👏 you are now registered.\nYour Healthcard ID is ";
$em["sub-menu-availability"] = "*When do you want to consult?*\n Type *1* - Today \n Type *2* - Tomorrow \n Type *3* - Day After Tomorrow";
$em["appt-booking-start"] = "Please wait while we are processing your request...";
$em["no-upcoming-appt"] = "Dear customer, currently you have no upcoming appointments.\n\nPlease type *hi* *{0}* to book new appointment.";
$em["labtest-upload"] = "Please send us your prescription";
$em["labtest-upload-success"] = "Thank you for uploading the prescription. We will get in touch soon for next steps.";
$em["existing-patientid"] = "Your Healthcard ID is ";

$em["thankyou-services"] = "I hope we have provided you with the resolution you were looking for.\n\n Please type *hi* *{0}*, and we will be to happy to help you again!
\n\nWishing you good health🌻";

$em["video-appt-paymentlink"] = "Please use the above link to make the payment so that we can confirm your appointment and send you a video consultation link. 
\n_(Payment link will remain active for 15 minutes. If you are facing any issues with payment, please call our Helpline {0})_";

$em["in-progress-transaction"] = "Dear customer, please wait while we are processing your request...";
$em["video-appt-booking-confirmation"] = "Your video appointment (ID:{appt_id}) has been booked with *{doctor}* for *{time}*\n\n. 
Please click the link below to start the video consult👇\n.{link}\n\nTry to reach here a few minutes before the appointment time so that you can go through the disclaimer and be ready for the video.";

//negative scenario's
$em["incorrect-option"] = "Please provide correct option to proceed";
$em["incorrect-input"] = "Please provide correct input to proceed";
$em["doctors-not-available"] = "No doctors are available right now😔. In case of emergency please contact our helpline.";
$em["depts-not-available"] = "Doctor is not available right now😔. In case of emergency please contact our helpline.";

$em["go-back-press-0"] = "_(Type *0* to go back to previous option)_";
$em["error-occurred"] = "Some error has occurred. Please try again😔.";
$em["session-expiry"] = "Dear customer, this conversation is now closed due to inactivity. Please type *hi* *{0}*, and we will be happy to help you again!";
$em["services-not-available"] = "No doctors are available right now😔. Try again tomorrow by sending *hi* *{1}*.\n\nIn case of emergency please contact our helpline {0}." . 
"\n\nWishing you good health🌻";

/*"We have taken a note of your health concerns. Our agents will connect with you as soon as possible. If you are facing any emergency, please call our Helpline {0}. 
You can also message us during business hours by sending *hi* or *🙏* followed by *{1}*, and we will be to happy to help. */

$em["session-exit"] = "Dear customer, this conversation is now closed.\n\nPlease type *hi* *{0}*, and we will be happy to help you again!";
$em["no-health-records"] = "Dear customer, currently you have no health records associated with us.";

$em["book-lab-test"] = "Thank you for placing lab test request🙏 We will reach out shortly for next steps!" .
"\nIn the meanwhile you can upload prescriptions and other documents here: <link to be provided>" .  
"\n\nWishing you good health🌻";

class MessageKeys
{
    public static $Welcome = "welcome";
    public static $ReaffirmWelcome = "reaffirm-welcome";
    public static $FullName = "fullname";
    public static $Age = "age";
    public static $Gender = "gender";
    public static $MainMenu = "main-menu";
    public static $SpecialitySelection = "speciality-selection";
    public static $DoctorSelection = "doctor-selection";
    public static $Symptom = "symptom";
    public static $RegistrationComplete = "registration-complete";
    public static $SubMenuAvailabilty = "sub-menu-availability";
    public static $ApptBookingStart = "appt-booking-start";
    public static $NoUpcomingAppt = "no-upcoming-appt"; 
    public static $LabTestUpload = "labtest-upload";
    public static $LabTestUploadSuccess = "labtest-upload-success";
    public static $ExistingPatientId = "existing-patientid";
    public static $IncorrectOption = "incorrect-option";
    public static $NameGreeting = "name-greeting";
    public static $GoBackPress0 = "go-back-press-0";
    public static $ServicesNotAvailable = "services-not-available";
    public static $IncorrectInput = "incorrect-input";
    public static $ThankYouServices = "thankyou-services";
    public static $VideoApptLinkPayment = "video-appt-paymentlink";
    public static $SessionExpiry = "session-expiry";
    public static $InPogressTransaction = "in-progress-transaction";
    public static $VdoApptBookingConfirmation = "video-appt-booking-confirmation";
    public static $NoHealthRecords = "no-health-records";
    public static $SessionExit = "session-exit";
    public static $ProfilePic = "profile-pic";
    public static $ProfilePicUpdate = "profile-pic-update";
    public static $InvalidProfilePic = "invalid-profile-pic";    
    public static $ProfilePicUpdateSuccessful = "profile-pic-update-success";
    public static $BookALabTest = "book-lab-test";
}

class BotLanguage
{
    public static $English = 1;
    public static $Hindi = 2;
}

function getLocalizedMessage($key)
{
    global $em;
    return $em[$key];
} 

function constructMainMenu($mainMenuMessageArr)
{
    $mainMenuText = '';
    foreach ($mainMenuMessageArr as $key => $value)
    {
        $mainMenuText = $mainMenuText . 'Type ' . '*' . $key . '* - ' . $value . "\n";
    }

    return $mainMenuText;
}
?>