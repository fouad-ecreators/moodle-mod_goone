<?php

require_once($CFG->libdir.'/completionlib.php');
$config = get_config('mod_goone');

//saves instance, should sperate these functions that do the zip shit, also add verification
function goone_add_instance($data, $mform = null){
    
    global $CFG, $DB;
   
    $cmid = $data->coursemodule;

    $data->timecreated =  time();    
    $data->id = $DB->insert_record('goone', $data);

    $fp = fopen($CFG->dataroot.'/GO1/'.$data->loid.'.zip', 'w+');
    $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.GO1.com/v2/learning-objects/".$data->loid."/scorm",
          CURLOPT_RETURNTRANSFER => false,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_BINARYTRANSFER => true,
          CURLOPT_FILE => $fp,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"limit\"\r\n\r\n1\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
          CURLOPT_HTTPHEADER => array(
            "Authorization:  Bearer ".get_config('mod_goone', 'token'),
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
          ),
        ));

        curl_exec($curl);
        fclose($fp);

        $zipconf = fopen('zip://'.$CFG->dataroot.'/GO1/'.$data->loid.'.zip#config.js', 'r');
        $zipres = fread($zipconf, 8192);

        $data->token = $zipres;
        fclose ('zip://'.$CFG->dataroot.'/GO1/'.$data->loid.'.zip#config.js', 'r');


    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = context_module::instance($cmid);
    $DB->update_record('goone', $data);


 //   goone_grade_item_update($goone);


    return $data->id;
}

function goone_update_instance($data, $mform){
    
    global $CFG, $DB;

    //Update goone main table
    $cmid               = $data->coursemodule;
    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision     = $data->revision++;
    $DB->update_record('goone', $data);

    //Update goone lessons
    /*Remove existing lessons on this activity and insert updates lessons vs. 
        Update all lessons on this activity- which requires an id param in update_record(), will need to retrieve the table id of the lesson using gooneid and lessonid.
        To get the lesson_id to be replaced, get all lessons inside this goone activity and loop to find the old lesson_id to be used for the id param ugh*/



    $DB->set_field('course_modules', 'instance', $data->instance, array('id'=>$cmid));
    
    return true;
}

function goone_delete_instance($id){
    global $DB;

    if (!$goone = $DB->get_record('goone', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('goone', $id);
    $DB->delete_records('goone', array('id'=>$goone->id));

    return true;
}

function goone_get_popup_display_array() {
    return array(0 => get_string('currentwindow', 'scorm'),
                 1 => get_string('popup', 'scorm'));
}



//not sure if needed anymore
function notify_completion($obj){
    global $DB, $USER;

    $goone = $DB->get_record('goone', array('id'=> $obj->activityId));
    
    $cm  = get_coursemodule_from_id('goone', $obj->courseModuleId, 0, false, MUST_EXIST); 
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST); 

    // Update completion state
    $completion = new completion_info($course);

    if($completion->is_enabled($cm) && $goone->completionsubmit) {
       $b = $completion->update_state($cm,COMPLETION_COMPLETE, $USER->id);
    }
}
/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function goone_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        // case FEATURE_BACKUP_MOODLE2:
        //     return true;

        

        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this goone based on any conditions
 * in goone settings.
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */

//retreives completion state for moodle, i dont know why its sql i just copied moodle
function goone_get_completion_state($course,$cm,$userid,$type) {
    global $CFG,$DB;

    // Get goone details
    if (!($goone=$DB->get_record('goone',array('id'=>$cm->instance)))) {
        throw new Exception("Can't find goone {$cm->instance}");
    }
            
    // $result=$type; // Default return value    
    $params = array('userid'=>$userid,'gooneid'=>$goone->id);
    $sql = "
        SELECT 
            completed
        FROM
            {goone_completion} 
        WHERE
            userid=:userid AND gooneid=:gooneid";
    
    $loComplete = $DB->get_field_sql($sql, $params);
    if ($goone->completionsubmit) {
        $result = ($loComplete == 2) ? true : false;
    }else {
        // Completion option is not enabled so just return $type
        return $type;
    }

    

    return $result;
}



//generate token from api credentials
function goone_generatetoken(){

    global $CFG, $DB;

    $oauthid = get_config('mod_goone', 'client_id');
    $oauthsecret = get_config('mod_goone', 'client_secret');
    $data = array ('client_id' => $oauthid,
                   'client_secret' => $oauthsecret,
                   'grant_type' => 'client_credentials');
    $curl = curl_init();


curl_setopt_array($curl, array(
  CURLOPT_URL => "https://auth.GO1.com/oauth/token",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $data,
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
$response = json_decode($response);
// set_config("GO1token",$response->access_token);
set_config('token', $response->access_token, 'mod_goone');

}

}

//tests token to check if it is valid
function goone_tokentest(){

    global $CFG, $DB;

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://auth.GO1.com/oauth/validate",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HEADER => true, 
  CURLOPT_NOBODY => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".get_config('mod_goone', 'token'),
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if($httpcode == 200)
        {return true;}
else {
goone_generatetoken();

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://auth.GO1.com/oauth/validate",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HEADER => true, 
  CURLOPT_NOBODY => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".get_config('mod_goone', 'token'),
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if($httpcode == 200)
        {return true;}
        else{return false;}
}
}
//retreives result hits in content browser
function goone_hits($ftype){
if($ftype == "all"){
    $psub = "";
    $pcoll = "";
}
if($ftype == "prem"){
    $psub = "true";
}
if($ftype == "coll"){
    $pcoll = "default";
    $psub = "";
}
    $data = array (
'type' => $ptype,
'subscribed' => $psub,
'collection' => $pcoll,
'limit' => 0,
'marketplace' => $pmark);
// var_dump($data);die;
foreach($data as $key=>$value)
        if(!$value==''){
                $params .= $key.'='.$value.'&';
         }
        $params = trim($params, '&');

$curl = curl_init();

curl_setopt_array($curl, array(
 CURLOPT_URL => "https://api.GO1.com/v2/learning-objects?marketplace=all&".$params,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"limit\"\r\n\r\n1\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".get_config('mod_goone', 'token'),
    "cache-control: no-cache",
    "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
  ),
));

$response = curl_exec($curl);
curl_close($curl);
$response = json_decode($response,true);
return number_format($response['total']);
}
//outputs scorm session state based on completion record
// 0 = not started, 1 = in progress, 2 = complete
function goone_session_state($gooneid,$cmid){
    global $CFG, $DB, $PAGE, $USER;
    $completionRecord = $DB->get_field('goone_completion', 'completed', array('gooneid'=>$gooneid,'userid'=>$USER->id));
    if ($completionRecord->completed == 0) {
        $def->{(3)} = goone_scorm_def(1,'');
        $def->{(6)} = goone_scorm_def(1,'');
        $cmistate = "normal";
    }
    if ($completionRecord->completed == 1) {
        $def->{(3)} = goone_scorm_def(1,'');
        $def->{(3)} = goone_scorm_def(2,$completionRecord->location);
        $cmistate = "normal";
    }
    if ($completionRecord->completed == 2) {
        $def->{(3)} = goone_scorm_def(3,'');
        $def->{(6)} = goone_scorm_def(4,'');
        $cmistate = "review";
    }
    
    $cmiobj = new stdClass();
    $cmiobj->{3} = '';
    $cmiobj->{6} = '';
    $cmiint = new stdClass();
    $cmiint->{3} = '';
    $cmiint->{6} = '';
    $cmistring256 = '^[\\u0000-\\uFFFF]{0,64000}$';
    $cmistring4096 = $cmistring256;

    $PAGE->requires->js_init_call('M.scorm_api.init', array($def, $cmiobj, $cmiint, $cmistring256, $cmistring4096, false, "0", "0", $CFG->wwwroot, sesskey(), "6", "1", $cmistate, $cmid, "GO1", false, true, "3"));
    //return;
 }

 //returns def variable for scorm session state
 function goone_scorm_def($state,$location) {
    global $USER;

    if ($state == 1) {
        $cmiCredit = "credit";
        $cmiEntry = "ab-initio";
        $cmiMode = "normal";
        $cmiLocation = "";
        $cmiStatus = "";
        $cmiMax = "";
        $cmiMin = "";
        $cmiExit = "";
    }
    if ($state == 2) {
        $cmiCredit = "credit";
        $cmiEntry = "resume";
        $cmiMode = "normal";
        $cmiLocation = $location;
        $cmiStatus = "incomplete";
        $cmiMax = "";
        $cmiMin = "";
        $cmiExit = "suspend";
    }
    if ($state == 3) {
        $cmiCredit = "no-credit";
        $cmiEntry = "ab-initio";
        $cmiMode = "review";
        $cmiLocation = "";
        $cmiStatus = "";
        $cmiMax = "";
        $cmiMin = "";
        $cmiExit = "";
    }
    if ($state == 4) {
        $cmiCredit = "no-credit";
        $cmiEntry = "";
        $cmiMode = "review";
        $cmiLocation = "";
        $cmiStatus = "passed";
        $cmiMax = "100";
        $cmiMin = "0";
        $cmiExit = "";
    }

    $def = array();
    $def['cmi.core.student_id'] = $USER->username;
    $def['cmi.core.student_name'] = $USER->firstname.' '.$USER->lastname;
    $def['cmi.core.credit'] = $cmiCredit;
    $def['cmi.core.entry'] = $cmiEntry;
    $def['cmi.core.lesson_mode'] = $cmiMode;
    $def['cmi.launch_data'] = '';
    $def['cmi.student_data.mastery_score'] = '';
    $def['cmi.student_data.max_time_allowed'] = '';
    $def['cmi.student_data.time_limit_action'] = '';
    $def['cmi.core.total_time'] = '00:00:00';
    $def['cmi.core.lesson_location'] = $cmiLocation;
    $def['cmi.core.lesson_status'] = $cmiStatus;
    $def['cmi.core.score.raw'] = $cmiMax;
    $def['cmi.core.score.max'] = $cmiMax;
    $def['cmi.core.score.min'] = $cmiMin;
    $def['cmi.core.exit'] = $cmiExit;
    $def['cmi.suspend_data'] = '';
    $def['cmi.comments'] = '';
    $def['cmi.student_preference.language'] = '';
    $def['cmi.student_preference.audio'] = '0';
    $def['cmi.student_preference.speed'] = '0';
    $def['cmi.student_preference.text'] = '0';
    return $def;

 }

//sets completion records in completion table along with scorm cmi location track
//works with datamodel.php
 function goone_set_completion($cm,$userid,$location,$type) { 
    global $CFG, $DB;
   // $goonid = IDONTKNOW
    $gcomp = new stdClass();
    $gcomp->userid = $userid;
    $gcomp->gooneid = $cm->instance;
    $gcomp->position = $location;

    $compstate = $DB->get_record('goone_completion', array('gooneid'=> $cm->instance,'userid' => $userid), 'id,completed',$strictness=IGNORE_MISSING);
    if($compstate->completed == 2) {
        return true;
    }

    if ($type == "completed") {
    $gcomp->completed = 2;
    $course = new stdClass();
    $course->id = $cm->course;    
    $completion = new completion_info($course);
    if($completion->is_enabled($cm)) {
    $completion->update_state($cm,COMPLETION_COMPLETE,$userid);
    }

    }
    if ($type == "inprogress") {
        $gcomp->position = $location;
        $gcomp->completed = 1;
    }
    if ($compstate) {
    $gcomp->id = $compstate->id;
    $DB->update_record('goone_completion', $gcomp);
    return true;
    } else {
    $DB->insert_record('goone_completion', $gcomp);
    return true;
    }
 }
 //gets scorm_12.js file form mod_scorm, modifies the datamodelurl variable, stores in cache and retreives it.
 function goone_inject_datamodel() {
    global $CFG;
        $cache = cache::make('mod_goone', 'scorm12datamodel');

    if($data = $cache->get('scorm12js')) {
    return $data;
    } 
    $data=file($CFG->dirroot.'/mod/scorm/datamodels/scorm_12.js');
    $data= "<script type=\"text/javascript\">".implode("", str_replace("/mod/scorm/datamodel.php", "/mod/goone/datamodel.php",$data))."</script>";
    $cache->set('scorm12js', $data);
    $data = $cache->get('scorm12js');
    return $data;
}