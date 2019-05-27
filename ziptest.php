<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

echo("code: </br>");
echo($code);
echo("</br>client_id</br>");
echo($client_id);
echo("</br>client_secret</br>");

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/goone/ziptest.php');
$PAGE->set_pagelayout('popup');

echo $OUTPUT->header();
echo $OUTPUT->footer();