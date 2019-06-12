<?php

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');   
    }
    
    require_once($CFG->dirroot.'/course/moodleform_mod.php');
    require_once($CFG->dirroot.'/mod/goone/lib.php');
    $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/form.js'));


    class mod_goone_mod_form extends moodleform_mod {
    
        function definition() {
            global $CFG, $DB, $OUTPUT, $PAGE;
            
            $mform =& $this->_form;

            $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', 'Please supply a name for this activity', 'required', null, 'client');
            $burl = new moodle_url('/mod/goone/browser.php');
            $mform->addElement('button', 'Content Browser', get_string('lobrowser','goone'), array('onclick'=>'openBrowser()'));

            $mform->addElement('text', 'loid', get_string('selectedloid','goone'), array('size'=>'16','readonly'));
            $mform->setType('loid', PARAM_TEXT);
            $mform->addRule('loid', 'Please use the Content Browser to select GO1 Content', 'required', null, 'client');
            $mform->addElement('text', 'loname', get_string('selectedloname','goone'), array('size'=>'64','readonly'));
            $mform->setType('loname', PARAM_TEXT);
            $mform->addRule('loname', 'Please use the Content Browser to select GO1 Content', 'required', null, 'client');

             $mform->addElement('select', 'popup', get_string('display', 'scorm'),  goone_get_popup_display_array());

            $cmid    = isset($_GET['id']) ? $_GET['id'] : @$_GET['update'];

            //-------------------------------------------------------------------------------
            $this->standard_coursemodule_elements();
            //-------------------------------------------------------------------------------

            $this->add_action_buttons();
        }

        /**
         * Add any custom completion rules to the form.
         *
         * @return array Contains the names of the added form elements
         */
        public function add_completion_rules() {
            $mform =& $this->_form;

            $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completionlo', 'goone'));
            // Enable this completion rule by default.
            $mform->setDefault('completionsubmit', 1);
            return array('completionsubmit');
        }

        /**
         * Determines if completion is enabled for this module.
         *
         * @param array $data
         * @return bool
         */
        public function completion_rule_enabled($data) {
            return !empty($data['completionsubmit']);
        }

    }

