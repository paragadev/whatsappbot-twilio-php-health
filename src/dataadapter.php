<?php
require 'dbhelper.php';
require 'memcached.php';

// registration model
class WARegistration
{
    public $id;
    public $patient_id;
    public $current_step;
    public $whatsapp_number;
    public $name;
    public $hid_code;
    public $hid;
    public $age;
    public $gender;
    public $speciality;
    public $doctor;
    public $symptom;
    public $confirmation_code;
    public $payment_done;
    public $workflow_completed;
    public $main_menu_option;
    public $sub_menu_option;
    public $appt_id;
    public $last_updated;
    public $profile_pic;    
}

class WADataProvider
{
    // Underlying EHR API endpoints
    private $matchMobileNumberAPI = "<existing-userid-check-api-using-mobile-number>";
    private $registerPatientAPI = "<patient-registration-api>";
    private $patientupcomingApptAPI = "<upcoming-appointments-api>";
    private $getDeptsByHidAPI = "<get-hospital-departments-api>";
    private $getDocsByHidAndDeptIdAPI = "<get-hospital-department-doctors-api>";
    private $bookAppInPersonAPI = "<book-in-person-appointment-api>";
    private $bookApptVideoAPI = "<book-video-appointment-api>";
    private $getHealthRecordsAPI = "get-health-records-api>";
    private $updatePatientProfilePicAPI = "<upload-user-profile-pic-api>";

    static private $instance;
    public $mcclient;
    public $db;
    static $hospitals;

    private $MCKEY_DOMAIN_HOSP = "DomainHospitals";

    public function __construct()
	{
        $this->mcclient = new MyMemcached();
        $this->mcclient->addServer('<memcachedserver>', 11211);
        $this->db = Database::instance();
        //WADataProvider::$hospitals = $this->constructHospitals();
	}

    static public function instance()
    {
		if (!isset(self::$instance)) {
			$name = __CLASS__;
			self::$instance = new $name;
		}

		return self::$instance;        
    }

    private function constructHospitals()
    {
        $tmp = array();
        $opts = array();
        $opts['fields'] = array('hid','code','title','gmap_url','helpline');
        $opts['where'] = array('isactive=1');
        $results = $this->db->select('admin_hospitals', $opts);

        foreach ($results as $row)
        {
            $code = trim($row['code']);
            $hid = trim($row['hid']);
            $tmp[$code] = array('hid' => $hid, 'code' => $code, 'desc' => $row['title'], 
                                        'url' => $row['gmap_url'], 'helpline' => $row['helpline']);
        }

        $this->mcclient->set($this->MCKEY_DOMAIN_HOSP, $tmp);
    }

    public function getHospitalDetails($code)
    {
        $hospDict = $this->mcclient->get($this->MCKEY_DOMAIN_HOSP);
        if (!$hospDict)
        {
            $this->constructHospitals();
            $hospDict = $this->mcclient->get($this->MCKEY_DOMAIN_HOSP);
            return $hospDict[$code];
        }

        return $hospDict[$code];
    }

    // check in database and return -1 if record does not exist.
    // return 0 if record exist but workflow steps are not initialized. 
    // greater than > 0 indicating current workflow step.  
    public function isActiveWorkflow($userWhatsApp)
    {
        $opts = array();
        $opts['fields'] = array('current_step');
        $opts['where'] = array('whatsapp_number=' . $userWhatsApp, 'workflow_completed=' . 0);
        $results = $this->db->select('patient_registration', $opts);
    
        if ($results != null) {
            if ($results) {
                $tmp = $results[0];
                $step = (int)$tmp['current_step'];

                //Todo: initialize WAregistration model and put in cache.
                return $step;
            } else {
                return -1;
            }
        } else {
            return -1;
        }        
    }

    public function markWorkflowComplete($id)
    {
        $whereParams = array();        
        $whereParams['where'] = array('id=' . $id, 'workflow_completed=0');
        $updateParams = [];
        $updateParams['workflow_completed'] = 1;
        $this->db->update('patient_registration', $updateParams, $whereParams);       
    }

    // todo: check in cache first, then fetch from db.
    public function getInProgressRegistrationDetails($mobile)
    {
//$startTime = new DateTime();   

        $opts = array();
        $opts['fields'] = array('id','current_step','name','whatsapp_number','hid','hid_code','age','gender','reg_id','speciality',
                                'doctor','main_menu_option','sub_menu_option','symptom','appt_id','payment_done','updated_on');
        $opts['where'] = array('whatsapp_number=' . $mobile, 'workflow_completed=' . 0);
        $results = $this->db->select('patient_registration', $opts);
    
        if ($results != null) {
            if ($results) {
                $tmp = $results[0];

                $obj = new WARegistration();
                $obj->id = $tmp['id'];
                $obj->current_step = $tmp['current_step'];
                $obj->name = $tmp['name'];
                $obj->whatsapp_number = $mobile;
                $obj->hid = $tmp['hid'];
                $obj->hid_code = $tmp['hid_code'];
                $obj->age = $tmp['age'];
                $obj->gender = $tmp['gender'];
                $obj->patient_id = $tmp['reg_id'];
                $obj->speciality = $tmp['speciality'];
                $obj->main_menu_option = $tmp['main_menu_option'];
                $obj->sub_menu_option = $tmp['sub_menu_option'];
                $obj->doctor = $tmp['doctor'];
                $obj->symptom = $tmp['symptom']; 
                $obj->appt_id = $tmp['appt_id'];
                $obj->last_updated = $tmp['updated_on'];

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in getInProgressRegistrationDetails = ' . $diff->s;*/
                                
                return $obj;
            } 
            else {
                return null;
            }
        } else {
            return null;
        }        
    }
 
   // returns lastId inserted into table
    public function addFirstTimeRegistration($whatsapp_number, $hid, $hid_code, $current_step, $name, $age, $gender, $reg_id)
    {
//$startTime = new DateTime();

        $insertData = array();
        $insertData['whatsapp_number'] = $whatsapp_number;
        $insertData['current_step'] = $current_step;
        $insertData['hid'] = $hid;
        $insertData['hid_code'] = $hid_code;
        $insertData['workflow_completed'] = 0;

        if ($name) {
            $insertData['name'] = $name;
        }

        if ($age) { 
            $insertData['age'] = $age;
        }

        if ($gender) {
            $insertData['gender'] = $gender;
        }

        if ($reg_id) {
            $insertData['reg_id'] = $reg_id;    
        }

        $this->db->insert('patient_registration', $insertData);

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in addFirstTimeRegistration = ' . $diff->s;
*/

        //Todo: Add in cache as well. 
        return $this->db->lastId();
    }

    public function updateInProgressRegistrationDetails(WARegistration $waReg)
    {
//$startTime = new DateTime();

        $whereParams = array();        
        $whereParams['where'] = array('id=' . $waReg->id, 'workflow_completed=0');
        $updateParams = [];

        if ($waReg->name) {
            $updateParams["name"] = $waReg->name;
        }

        if ($waReg->current_step) {
            $updateParams["current_step"] = $waReg->current_step;
        }

        if ($waReg->age) {
            $updateParams["age"] = $waReg->age;
        }
        
        if ($waReg->gender) {
            $updateParams["gender"] = $waReg->gender;
        }

        if ($waReg->speciality) {
            $updateParams["speciality"] = $waReg->speciality;
        }

        if ($waReg->symptom) {
            $updateParams["symptom"] = $waReg->symptom;
        }

        if ($waReg->doctor) {
            $updateParams["doctor"] = $waReg->doctor;            
        }

        if ($waReg->main_menu_option) {
            $updateParams["main_menu_option"] = $waReg-> main_menu_option;            
        }

        if ($waReg->sub_menu_option) {
            $updateParams["sub_menu_option"] = $waReg->sub_menu_option;            
        }

        if ($waReg->appt_id) {
            $updateParams["appt_id"] = $waReg->appt_id;            
        }
        
        if ($waReg->workflow_completed) {
            $updateParams["workflow_completed"] = $waReg->workflow_completed;                        
        }

        $this->db->update('patient_registration', $updateParams, $whereParams);

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in updateInProgressRegistrationDetails = ' . $diff->s;
*/
    }

    // Section: EHR API's
    public function checkIfUserAlreadyRegistered($userWhatsApp)
    {
//$startTime = new DateTime();

        $postData = '{"mobile":"{0}"}';
        $postData = str_replace("{0}", $userWhatsApp, $postData);
        $response = $this->postJson($this->matchMobileNumberAPI, $postData);
        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                $waReg = new WARegistration();
                if ($obj->healthcard_id) {
                    $waReg->patient_id = $obj->healthcard_id;
                }

                $waReg->name = $obj->name;
                $waReg->age = $obj->age;
                
                if ($obj->gender == "Male") {
                    $waReg->gender = "1";
                }

                if ($obj->gender == "Female") {
                    $waReg->gender = "2";
                }
                
/*
$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in checkIfUserAlreadyRegistered = ' . $diff->s;
*/

                return $waReg;
            }

            return false;
        }

        return false;
    }

    // first complete the registration process, call web service to register the patient and clear the cache. 
    public function registerPatient(WARegistration $waReg)
    {

//$startTime = new DateTime();

        $genderStr = '';
        if ($waReg->gender == '1') {
            $genderStr = "male";
        }

        if ($waReg->gender == '2') {
            $genderStr = "female";
        }

        $postDataArr = ["name" => $waReg->name, "mobile" => $waReg->whatsapp_number, "age" => $waReg->age, "gender" => $genderStr, "hid" => $waReg->hid];
        $postData = json_encode($postDataArr);
        $response = $this->postJson($this->registerPatientAPI, $postData);

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in registerPatient API = ' . $diff->s;                
*/                
        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                $waReg->patient_id = $obj->patient_id;
                
                // update bot table regarding reg id.        
                $whereParams = array();        
                $whereParams['where'] = array('id=' . $waReg->id, 'workflow_completed=0');
                $updateParams = [];
                $updateParams["reg_id"] = $waReg->patient_id;
                $this->db->update('patient_registration', $updateParams, $whereParams);

                return $waReg;
            }

            return false;
        }

        return false;
    }

    public function updatePatientProfilePic($patient_id, $patient_profile_pic_url)
    {
        $postDataArr = ["pid" => $patient_id, "image_url" => $patient_profile_pic_url];
        $postData = json_encode($postDataArr);
        $this->postJson($this->updatePatientProfilePicAPI, $postData);
/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in registerPatient API = ' . $diff->s;                
*/                
    }

    public function getDepartments($waRegObj)
    {
//$startTime = new DateTime();

        $sub_menu_option = $waRegObj->sub_menu_option;
        $hid = $waRegObj->hid;
        $response = '';        

        $postDataArr = ["hid" => $hid, "day_count" => $sub_menu_option];
        $postData = json_encode($postDataArr);                        
        $response = $this->postJson($this->getDeptsByHidAPI, $postData);

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in getDepartments API = ' . $diff->s;  
*/
        $txt = '';
        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                foreach($obj->departments->results as $i => $i_value) {
                    $txt = $txt . 'Type ' . '*' . $i_value->id . '* - ' . $i_value->name . "\n";
                }
                
                return $txt;
            }

            return false;
        }

        return false;        
    }

    public function getDoctors($waRegObj)
    {
//$startTime = new DateTime();
        
        $sub_menu_option = $waRegObj->sub_menu_option;
        $response = '';        
        $postDataArr = ["hid" => $waRegObj->hid, "dept_id" => $waRegObj->speciality, "day_count" => $sub_menu_option];
        $postData = json_encode($postDataArr);
        $response = $this->postJson($this->getDocsByHidAndDeptIdAPI, $postData);

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in getDoctors API = ' . $diff->s;          
*/        
        $txt = '';
        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                foreach($obj->Doctors->results as $i => $i_value) {
                    $txt = $txt . 'Type ' . '*' . $i_value->id . '* - ' . $i_value->name . " (" . $i_value->speciality . ") \n";
                }
                
                return $txt;                
            }

            return false;
        }

        return false;                
    }

    public function bookAppointment(WARegistration $waReg)
    {
//$startTime = new DateTime();

        $bookingResponse = '';
        $response = '';

        // in person appt 
        if ($waReg->main_menu_option == '1')
        {
            $postDataArr = ["hid" => $waReg->hid, "did" => $waReg->doctor, "pid" => $waReg->patient_id, 
            "chiefcompliant" => $waReg->symptom, "day_count" => $waReg->sub_menu_option, "encounter_type" => "1"];
            $postData = json_encode($postDataArr);
            $response = $this->postJson($this->bookAppInPersonAPI, $postData); 

            if ($response)
            {
                $obj = json_decode($response);
                if ($obj->status == '1')
                {
                    $bookingResponse = $obj->message . "\n\n" . str_replace("{0}", $waReg->hid_code, getLocalizedMessage(MessageKeys::$ThankYouServices)); 

                    // bot - complete workflow = 1 
                    $whereParams = array();        
                    $whereParams['where'] = array('id=' . $waReg->id, 'workflow_completed=0');
                    $updateParams = [];
                    $updateParams['workflow_completed'] = 1;
                    $updateParams['current_step'] = $waReg->current_step;
                    $updateParams['symptom'] = $waReg->symptom;
                    
                    //todo: update appt id as well.

                    $this->db->update('patient_registration', $updateParams, $whereParams);       
                }
            }
        }
        
        // video appt
        if ($waReg->main_menu_option == '2')
        {
            $postDataArr = ["hid" => $waReg->hid, "did" => $waReg->doctor, "pid" => $waReg->patient_id, 
            "chiefcompliant" => $waReg->symptom, "day_count" => $waReg->sub_menu_option, "encounter_type" => "2"];
            $postData = json_encode($postDataArr);
            $response = $this->postJson($this->bookApptVideoAPI, $postData);
            if ($response)
            {
                $obj = json_decode($response);
                if ($obj->status == '1')
                {
                    $hospDetails = $this->getHospitalDetails($waReg->hid_code);
                    $bookingResponse = $obj->payment_link  . "\n\n" . str_replace("{0}", $hospDetails['helpline'], getLocalizedMessage(MessageKeys::$VideoApptLinkPayment));

                    $whereParams = array();        
                    $whereParams['where'] = array('id=' . $waReg->id, 'workflow_completed=0');
                    $updateParams = [];
                    $updateParams['current_step'] = $waReg->current_step;
                    $updateParams['symptom'] = $waReg->symptom;
                    //todo: update appt id as well.

                    $this->db->update('patient_registration', $updateParams, $whereParams);       
                }
            }
        }

/*$endTime = new DateTime();
$diff = date_diff($startTime, $endTime);
print "\n";    
echo 'Time spent in bookAppt API = ' . $diff->s;
*/
      return $bookingResponse;
    }

    public function viewUpcomingAppointments($mobile, $hid)
    {
        $postDataArr = ["mobile" => $mobile, "hid" => $hid];
        $postData = json_encode($postDataArr);
        $response = $this->postJson($this->patientupcomingApptAPI, $postData);

        $txt = '';
        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                foreach($obj->appointments->results as $i => $i_value) {
                    $txt = $txt . $i_value->message . "\n";
                }
                
                return $txt;                                
            }

            return false;
        }

        return false;                
    }

    public function getHealthRecords($pid)
    {
        $postDataArr = ["pid" => $pid];
        $postData = json_encode($postDataArr);
        $response = $this->postJson($this->getHealthRecordsAPI, $postData);

        if ($response)
        {
            $obj = json_decode($response);
            if ($obj->status == '1')
            {
                return $obj->message;                                
            }

            return false;
        }

        return false;                

    }

    private function postJson($endpoint, $postData)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;    
    }

    // bot API's
    // update video appt_id and mark workflow complete
    public function confirmVideoAppt($apptObj)
    {
        $whereParams = array();        
        $whereParams['where'] = array('whatsapp_number=' . $apptObj->mobile, 'workflow_completed=0');
        $updateParams = [];
        $updateParams['workflow_completed'] = 1;
        $updateParams['payment_done'] = 1;
        $updateParams['appt_id'] = $apptObj->appt_id;
        $this->db->update('patient_registration', $updateParams, $whereParams);
    }
}
?>