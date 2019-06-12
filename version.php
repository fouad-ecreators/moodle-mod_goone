<?php

defined('MOODLE_INTERNAL') || die;
/**
* Version information
*
* @package   mod_goone
* @copyright 2019 Fouad Saikali <fouad@ecreators.com.au>
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

$plugin->component = 'mod_goone';
$plugin->version  = 2019060602.00;
$plugin->release  = 'v1.0'; 
$plugin->requires = 2015111600.00;
$plugin->maturity = MATURITY_STABLE; 
$plugin->dependencies = array(
    'mod_scorm' => ANY_VERSION,
);