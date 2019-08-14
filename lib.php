<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GO1 plugin function library
 *
 * @package   mod_goone
 * @copyright 2019, eCreators PTY LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Fouad Saikali <fouad@ecreators.com.au>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir.'/completionlib.php');
$config = get_config('mod_goone');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $data
 * @return int
 */
function goone_add_instance($data, $mform = null) {
    global $CFG, $DB;

    $cmid = $data->coursemodule;
    $data->timecreated = time();
    // Create temporary storage directory since we need to open a zip.
    $tempdir = make_temp_directory('goone/');
    $filename = $data->loid.'.zip';
    $tempfile = fopen($CFG->tempdir . '/goone/' . $filename, "w+");
    // Download GO1 SCORM zip file from external API.
    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects/".$data->loid."/scorm";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $curlopts = array(
        'file' => $tempfile,
        'followlocation' => true
        );
    $curl->download_one($serverurl, null, $curlopts);

    fclose($tempfile);
    // Open zip and extract 'config.js'.
    $packer = get_file_packer('application/zip');
    if ($packer->extract_to_pathname($CFG->tempdir . '/goone/' . $filename, $tempdir . $data->loid)) {
        $token = file_get_contents($tempdir . $data->loid . '/config.js');
        // Read token from config.js file to be stored in {goone} table.
        preg_match('/{([^}]*)}/', $token, $token);
        $token = json_decode($token[0]);
        $data->token = $token->token;
        fulldelete($tempdir.$filename);
        fulldelete($tempdir.$data->loid);
        if (!$token->token) {
            throw new moodle_exception('lodownloaderror', $data->loid);
        }
    } else {
        throw new moodle_exception('lodownloaderror', $data->loid);
    }
    $data->id = $DB->insert_record('goone', $data);
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    $context = context_module::instance($cmid);
    $DB->update_record('goone', $data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $data
 * @return bool
 */
function goone_update_instance($data, $mform) {
    global $CFG, $DB;

    $cmid               = $data->coursemodule;
    $data->timemodified = time();
    $data->id           = $data->instance;
    $DB->update_record('goone', $data);
    $DB->set_field('course_modules', 'instance', $data->instance, array('id' => $cmid));

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function goone_delete_instance($id) {
    global $DB;

    if (!$goone = $DB->get_record('goone', array('id' => $id))) {
        return false;
    }
    $cm = get_coursemodule_from_instance('goone', $id);
    $DB->delete_records('goone', array('id' => $goone->id));

    return true;
}

/**
 * Returns an array of options for how GO1 courses can be presented
 * This is used by the participation report.
 *
 * @return array
 */
function goone_get_popup_display_array() {
    return array(0 => get_string('currentwindow', 'scorm'),
                 1 => get_string('popup', 'scorm'));
}


// Not sure if needed anymore.
function notify_completion($obj) {
    global $DB, $USER;

    $goone = $DB->get_record('goone', array('id' => $obj->activityId));

    $cm  = get_coursemodule_from_id('goone', $obj->courseModuleId, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    // Update completion state
    $completion = new completion_info($course);

    if ($completion->is_enabled($cm) && $goone->completionsubmit) {
        $b = $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
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
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
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
function goone_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get goone details
    if (!($goone = $DB->get_record('goone', array('id' => $cm->instance)))) {
        throw new Exception("Can't find goone {$cm->instance}");
    }

    $params = array('userid' => $userid, 'gooneid' => $goone->id);
    $sql = "
        SELECT completed
        FROM {goone_completion}
        WHERE userid=:userid AND gooneid=:gooneid";

    $locomplete = $DB->get_field_sql($sql, $params);
    if ($goone->completionsubmit) {
        if ($locomplete == 2) {
            $result = true;
        } else {
            $result = false;
        }
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }

    return $result;
}



/**
 * Generates go token and writes result to plugin config
 *
 * @global object
 */
function goone_generatetoken() {
    global $CFG, $DB;

    $oauthid = get_config('mod_goone', 'client_id');
    $oauthsecret = get_config('mod_goone', 'client_secret');
    $params = array ('client_id' => $oauthid,
                   'client_secret' => $oauthsecret,
                   'grant_type' => 'client_credentials');

    $curl = new curl();
    $serverurl = "https://auth.GO1.com/oauth/token";
    $curloutput = @json_decode($curl->post($serverurl, $params), true);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        set_config('token', $curloutput['access_token'], 'mod_goone');
    }
}

/**
 * Generates go token and writes result to plugin config
 *
 * @global object
 * @return bool
 */
function goone_tokentest() {
    global $CFG, $DB;

    $config = get_config('mod_goone');
    if (empty($config->client_id) || empty($config->client_secret)) {
        set_config('token', '', 'mod_goone');
        return false;
    }

    $curl = new curl();
    $serverurl = "https://auth.GO1.com/oauth/validate";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $curl->get($serverurl);
    $httpcode = $curl->get_info()['http_code'];
    if ($httpcode == 200) {
        return true;
    } else {
        goone_generatetoken();

        $curl = new curl();
        $serverurl = "https://auth.GO1.com/oauth/validate";
        $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
        $curl->setHeader($header);
        $curl->get($serverurl);
        $httpcode = $curl->get_info()['http_code'];

        if ($httpcode == 200) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Returns number of results for each filter option in site admin settings
 *
 * @param string $ftype
 * @return int
 */
function goone_hits($ftype) {
    if (!goone_tokentest()) {
        return;
    }

    $psub = "";
    $pcoll = "";
    $psub = "";
    $params = "";

    if ($ftype == "all") {
        $psub = "";
        $pcoll = "";
    }
    if ($ftype == "prem") {
        $psub = "true";
    }
    if ($ftype == "coll") {
        $pcoll = "default";
        $psub = "";
    }

    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $params = array (
    'type' => '',
    'subscribed' => $psub,
    'collection' => $pcoll,
    'limit' => 0,
    'marketplace' => '');
    $hits = @json_decode($curl->get($serverurl, $params), true);

    return number_format($hits['total']);
}

/**
 * Removes HTML tags for course descriptions retreived from GO1 API
 *
 * @param object $data
 * @return object
 */
function goone_clean_hits($data) {

    $data = preg_replace('/\\\\u[0-9A-F]{4}/i', '', str_replace("\u003C", "<", str_replace("\u003E", ">", str_replace("\/", "/", $data))));
    $data = html_entity_decode($data);
    $data = preg_replace('(\s*<[a-z A-Z 0-9]*>\\s*)', '', $data);
    $data = preg_replace('(\s*<\/[a-z A-Z 0-9]*>\s*)', ' ', $data);
    $data = preg_replace('(\s*<[^>]*>\s*)', '', $data);

    return $data;
}

/**
 * Outputs session state to SCORM API (using PAGE API) based on saved location and completion data
 *
 * @global object
 * @param int $gooneid
 * @param int $cmid
 */
function goone_session_state($gooneid, $cmid) {
    global $CFG, $DB, $PAGE, $USER;

    $def = new stdClass;
    // 0 = not started, 1 = in progress, 2 = complete.
    $completionrecord = $DB->get_record('goone_completion', array('gooneid' => $gooneid, 'userid' => $USER->id), $fields = '*', $strictness = IGNORE_MISSING);

    $def->{(3)} = goone_scorm_def(1, '');
    $def->{(6)} = goone_scorm_def(1, '');
    $cmistate = "normal";

    if ($completionrecord && $completionrecord->completed == 1) {
        $def->{(3)} = goone_scorm_def(1, '');
        $def->{(6)} = goone_scorm_def(2, $completionrecord->location);
        $cmistate = "normal";
    }
    if ($completionrecord && $completionrecord->completed == 2) {
        $def->{(3)} = goone_scorm_def(3, '');
        $def->{(6)} = goone_scorm_def(4, '');
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

    $PAGE->requires->js_init_call('M.scorm_api.init', array($def, $cmiobj, $cmiint, $cmistring256, $cmistring4096, false, "0", "0", $CFG->wwwroot,
        sesskey(), "6", "1", $cmistate, $cmid, "GO1", false, true, "3"));
}

/**
 * Populates SCORM API definition for function goone_session_state
 *
 * @global object
 * @param int $state
 * @param string $location
 */
function goone_scorm_def($state, $location) {
    global $USER;

    if (!$location) {
        $location = "";
    }
    if ($state == 1) {
        $cmicredit = "credit";
        $cmientry = "ab-initio";
        $cmimode = "normal";
        $cmilocation = "";
        $cmistatus = "";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "";
    }
    if ($state == 2) {
        $cmicredit = "credit";
        $cmientry = "resume";
        $cmimode = "normal";
        $cmilocation = $location;
        $cmistatus = "incomplete";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "suspend";
    }
    if ($state == 3) {
        $cmicredit = "no-credit";
        $cmientry = "ab-initio";
        $cmimode = "review";
        $cmilocation = "";
        $cmistatus = "";
        $cmimax = "";
        $cmimin = "";
        $cmiexit = "";
    }
    if ($state == 4) {
        $cmicredit = "no-credit";
        $cmientry = "";
        $cmimode = "review";
        $cmilocation = "";
        $cmistatus = "passed";
        $cmimax = "100";
        $cmimin = "0";
        $cmiexit = "";
    }

    $def = array();
    $def['cmi.core.student_id'] = $USER->username;
    $def['cmi.core.student_name'] = $USER->firstname.' '.$USER->lastname;
    $def['cmi.core.credit'] = $cmicredit;
    $def['cmi.core.entry'] = $cmientry;
    $def['cmi.core.lesson_mode'] = $cmimode;
    $def['cmi.launch_data'] = '';
    $def['cmi.student_data.mastery_score'] = '';
    $def['cmi.student_data.max_time_allowed'] = '';
    $def['cmi.student_data.time_limit_action'] = '';
    $def['cmi.core.total_time'] = '00:00:00';
    $def['cmi.core.lesson_location'] = $cmilocation;
    $def['cmi.core.lesson_status'] = $cmistatus;
    $def['cmi.core.score.raw'] = $cmimax;
    $def['cmi.core.score.max'] = $cmimax;
    $def['cmi.core.score.min'] = $cmimin;
    $def['cmi.core.exit'] = $cmiexit;
    $def['cmi.suspend_data'] = '';
    $def['cmi.comments'] = '';
    $def['cmi.student_preference.language'] = '';
    $def['cmi.student_preference.audio'] = '0';
    $def['cmi.student_preference.speed'] = '0';
    $def['cmi.student_preference.text'] = '0';

    return $def;
}

/**
 * Saves GO1 course completion state if enabled.
 *
 * @global object
 * @param object $cm
 * @param int $userid
 * @param string $location
 * @param string $type
 * @return bool
 */
function goone_set_completion($cm, $userid, $location, $type) {
    global $CFG, $DB;

    $gcomp = new stdClass();
    $gcomp->userid = $userid;
    $gcomp->gooneid = $cm->instance;
    $gcomp->position = $location;
    $gcomp->timemodified = time();

    $compstate = $DB->get_record('goone_completion', array('gooneid' => $cm->instance, 'userid' => $userid), 'id,completed', $strictness = IGNORE_MISSING);

    if ($type == "completed" || $compstate->completed == 2) {
        $gcomp->completed = 2;
        $course = new stdClass();
        $course->id = $cm->course;
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
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

/**
 * gets scorm_12.js file form mod_scorm, modifies the datamodelurl variable, stores in cache and Retrieves it.
 *
 * @global object
 * @return object
 */
function goone_inject_datamodel() {
    global $CFG;

    $cache = cache::make('mod_goone', 'scorm12datamodel');

    if ($data = $cache->get('scorm12js')) {
        return $data;
    }
        $data = file($CFG->dirroot.'/mod/scorm/datamodels/scorm_12.js');
        $data = "<script type=\"text/javascript\">".
            implode("", str_replace("/mod/scorm/datamodel.php", "/mod/goone/datamodel.php", $data))."</script>";
        $cache->set('scorm12js', $data);
        $data = $cache->get('scorm12js');
        return $data;
}

/**
 * Retrieves GO1 search results from GO1 API for Content Browser
 *
 * @param array $params
 * @return object
 */
function goone_get_hits($params) {
    if (!goone_tokentest()) {
        return false;
    }

    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects?facets=instance,tag,language&marketplace=all&".$params;
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $response = @json_decode($curl->get($serverurl), true);
    $curl = curl_init();

    return $response;
}

/**
 * Retrieves GO1 search facets from GO1 API for Content Browser
 *
 * @param array $params
 * @return object
 */
function goone_get_facets() {
    global $USER;

    if (!goone_tokentest()) {
        return;
    }
    $curl = new curl();
    $serverurl = "https://api.GO1.com/v2/learning-objects";
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $params = array ('facets' => 'instance,tag,language',
                     'limit' => 0);
    $facets = @json_decode($curl->get($serverurl, $params), true);

    foreach ($facets['facets']['language']['buckets'] as &$obj) {
        $obj['name'] = goone_get_lang($obj['key']);
        if ($obj['key'] == $USER->lang) {
            $obj['selected'] = "selected";
        }
    }
    return $facets;
}

/**
 * Converts ISO language code to full language name for GO1 Content Browser
 *
 * @param string $lang
 * @return string
 */
function goone_get_lang($lang) {
    $languages = get_string_manager()->get_list_of_languages();
    if (array_key_exists($lang, $languages)) {
        return $languages[$lang];
    }
    if (strpos($lang, '-') > 0) {
        list($langcode, $countrycode) = explode('-', $lang, 2);
        if (array_key_exists($langcode, $languages)) {
            $string = $languages[$langcode]; $countrycode = clean_param(strtoupper($countrycode), PARAM_STRINGID);
            if (get_string_manager()->string_exists($countrycode, 'core_countries')) {
                return $string . " (" . get_string($countrycode, 'core_countries') . ")";
            }
        }
    }
    if (empty($lang)) {
        return get_string('unknownlanguage', 'mod_goone');
    }
        return $lang;
}

/**
 * Retreives detailed GO1 course information for modal popup in GO1 Content Browser
 *
 * @param int $loid
 * @return object
 */
function goone_modal_overview($loid) {
    if (!goone_tokentest()) {
        return;
    }
    $curl = new curl();
    $serverurl = "https://api.go1.com/v2/learning-objects/".$loid;
    $header = array ("Authorization: Bearer ".get_config('mod_goone', 'token'));
    $curl->setHeader($header);
    $lodata = @json_decode($curl->get($serverurl), true);
    // Data cleanup and prettification.
    $lodata['has_items'] = !empty($lodata['items']);
    foreach ($lodata['delivery'] as &$obj) {
        $obj = goone_convert_hours_mins($obj);
    }
    return $lodata;
}

/**
 * Converts timecode to human readable time for GO1 course durations from GO1 API results
 *
 * @param int $time
 * @param string $format
 * @return string
 */
function goone_convert_hours_mins($time, $format = '%02d:%02d') {
    if ($time < 1) {
        return;
    }
    $hours = floor($time / 60);
    $minutes = ($time % 60);

    return sprintf($format, $hours, $minutes);
}

/**
 * Capability check for adding or updating a goone activity
 *
 * @global object
 * @param string $mode
 * @param int $id
 */
function goone_check_capabilities($mode, $id) {
    global $DB;

    switch ($mode) {
        case 'add':
            // Check if course context capability allowed.
            $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
            $context = context_course::instance($course->id);
            require_capability('mod/goone:addinstance', $context);
            return;
        case 'update':
            // Check if module context capability allowed.
            $goone = $DB->get_record('goone', array('id' => $id), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance("goone", $goone->id, $goone->course);
            $context = context_module::instance($cm->id);
            require_capability('mod/goone:addinstance', $context);
            return;
        default:
            throw new moodle_exception('invalidparam');
    }
}