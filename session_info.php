<?php

global $CFG, $DB, $PAGE, $USER;

require_once('../../config.php');
require_once('lib.php');
require_once("{$CFG->libdir}/completionlib.php");

$cmid = required_param('id', PARAM_INT);

$params = optional_param('response', NULL, PARAM_TEXT);
$sessionInfo = json_decode($params);
$storedSession = saveSessionList($sessionInfo, $cmid);


// // Get goone details
// if (!($goone = $DB->get_record('goone',array('id'=>$sessionInfo->activityId)))) {
//     throw new Exception("Can't find goone {$cm->instance}");
// }
        
// // $result=$type; // Default return value    
// $params = array('user_id'=>$USER->id,'goone_id'=>$goone->id);
// $sql = "
//     SELECT 
//         percentage_completed
//     FROM
//         {goone_completion} 
//     WHERE
//         user_id=:user_id AND goone_id=:goone_id";

// $lessonComplete = $DB->get_field_sql($sql, $params);
// if ($goone->completionsubmit) {
//     echo 5;

//     echo 'result::' . $result = ($lessonComplete == 100) ? true : false;
// }else {

//     echo 4;
//     // Completion option is not enabled so just return $type
//     //return $type;
// }

//     echo "<pre>"; var_dump($goone); echo "<pre><br>";
//     echo "<pre>"; var_dump($params); echo "<pre><br>";
//     echo "<pre>"; var_dump($sql); echo "<pre><br>";
//     echo "<pre>"; var_dump($lessonComplete); echo "<pre><br>";
//     echo "<pre>"; var_dump($result); echo "<pre><br>";
    


// die;