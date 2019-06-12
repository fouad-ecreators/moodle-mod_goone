<?php
//TODO: Move javascript from mustache to AMD
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/goone/lib.php');    
require_login();
$config = get_config('mod_goone');

if (!goone_tokentest()) {
  echo $OUTPUT->notification(get_string('connectionerror', 'goone'), 'notifyproblem');
}
$facets = goone_get_facets();
$facets = json_decode($facets,true);


//default language selected
foreach ($facets['facets']['language']['buckets'] as &$obj) {
   // $obj['name'] = $stringmanager->get_language($obj['key']);
   $obj['name'] = goone_get_lang($obj['key']);
   if ($obj['key'] == $USER->lang) {
   $obj['selected'] = "selected";
  }
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/browser.php');
$PAGE->set_pagelayout('embedded');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/bootstrap-multiselect.js'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/goone/css/bootstrap-multiselect.css'));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_goone/browser', $facets);
echo $OUTPUT->footer(); 