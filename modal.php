<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php'); 
require_login();

$loid = required_param('loid', PARAM_RAW); 

$data = goone_modal_overview($loid);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/modal', $data);
echo $OUTPUT->footer();
