<?php

    require_once('../../config.php');
    require_once('lib.php');

    global $CFG, $DB, $OUTPUT, $PAGE, $USER;
    require_once($CFG->dirroot.'/mod/scorm/locallib.php');
    require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_12lib.php');
    $newwin = false; 
    $cmid   = required_param('id', PARAM_INT);
    $newwin  = optional_param('win','', PARAM_INT);  // Course Module ID   
    $cm     = get_coursemodule_from_id('goone', $cmid, 0, false, MUST_EXIST); 
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST); 
    if ($newwin == 1) {$newwin = true;}

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
    
    require_login($course, true, $cm);
     
    if (!$cm = get_coursemodule_from_id('goone', $cmid)) {
        print_error(get_string('cmidincorrect','goone'));
    }
    if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
        print_error(get_string('courseincorrect','goone')); 
    }
    if (!$goone = $DB->get_record('goone', array('id'=> $cm->instance))) {
        print_error(get_string('cmincorrect','goone')); 
    }

    $PAGE->set_url('/mod/goone/view.php', array('id' => $cm->id)); 
    $PAGE->set_title($goone->name); 
    $PAGE->requires->js_call_amd('mod_goone/viewer','init');


    $exiturl = course_get_url($course, $cm->section);
    $strexit = get_string('exitactivity', 'scorm');
    $exitlink = html_writer::link($exiturl, $strexit, array('title' => $strexit, 'class' => 'btn btn-default'));
    $PAGE->set_button($exitlink);

    //Handle opening in a new window
    if ($newwin == 1) {
    $PAGE->set_pagelayout('embedded');
    }
    $isnewwin = $DB->get_field('goone', 'popup', array('id' => $goone->id));
    if ($isnewwin == 1 && $newwin == 0) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($goone->name));
        echo "The GO1 Activity has been launched in a new window.";
        $urltogo = new moodle_url('/mod/goone/view.php', array('id'=>$cm->id));
        $PAGE->requires->js_call_amd('mod_goone/viewer','newwindow', array(($urltogo->__toString())));
        echo $OUTPUT->footer();
       return;
    }

//load all the goodies we need now for GO1 modules
    $PAGE->requires->js(new moodle_url('/lib/cookies.js'), true);
    $PAGE->requires->js(new moodle_url('/mod/scorm/module.js'), true);
    $PAGE->requires->js(new moodle_url('/mod/scorm/request.js'), true);

    echo $OUTPUT->header();
    echo(goone_inject_datamodel());

    if (!$newwin) {
    echo $OUTPUT->heading(format_string($goone->name));
    }
    goone_session_state($goone->id,$cmid);

?>

  <script type="text/javascript" src="https://api.go1.co/scorm/assets/jquery-1.12.4.min.js"></script>
  <script>
    "use strict";
    
const ScormPackage_Value = {
    "token": "<?php echo $DB->get_field('goone', 'token', array('id' => $goone->id)); ?>",
    "version": "1.2",
    "id": <?php echo $DB->get_field('goone', 'loid', array('id' => $goone->id)); ?>
};
  </script>
  <script type="text/javascript" src="https://api.go1.co/scorm/assets/service.js"></script>
  <div id="content"></div>

<?php  

echo $OUTPUT->footer();

   
