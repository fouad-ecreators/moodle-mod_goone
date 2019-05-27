<?php

    /*
        This file is used when adding/editing a module to a course. 
        It contains the elements that will be displayed on the form responsible for creating/installing an instance of your module. 
        The class in the file should be called mod_<modname>_mod_form.
    */
    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }
    
    require_once($CFG->dirroot.'/course/moodleform_mod.php');
    require_once($CFG->dirroot.'/mod/goone/lib.php');
    $PAGE->requires->js( new moodle_url($CFG->wwwroot . '/mod/goone/js/form.js'));

 //   include('goone_api.php');

    class mod_goone_mod_form extends moodleform_mod {
    
        function definition() {
            global $CFG, $DB, $OUTPUT, $PAGE;
            
            // $PAGE->requires->css(new moodle_url('/mod/goone/css/select2.min.css'));
            // $PAGE->requires->css(new moodle_url('/mod/goone/css/customgoone.css'));
            // $PAGE->requires->js(new moodle_url('/mod/goone/js/jquery-3.3.1.min.js'), true);
            // $PAGE->requires->js(new moodle_url('/mod/goone/js/select2.min.js'), true);
            // $PAGE->requires->js(new moodle_url('/mod/goone/js/customgoone.js'), true);
            
            $mform =& $this->_form;

            
            // //Data from the API
            // $lessonJson = getLessonList();  
            // $lesssonList = json_decode($lessonJson);
            
            // $apiMessage = ($lesssonList->lessons == NULL) ? '<div class="p-3 mb-2 bg-danger text-white">'. get_string('apiconnection_failed', 'goone') .'</b></div>' : '';

            $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', 'Please supply a name for this activity', 'required', null, 'client');
            $burl = new moodle_url('/mod/goone/browser.php');
            $mform->addElement('button', 'Content Browser', get_string('lobrowser','goone'), array('onclick'=>'openBrowser()'));


            // $url = new moodle_url('/blocks/progress/overview.php', $parameters);
            // $label = get_string('overview', 'block_progress');
            // $options = array('class' => 'overviewButton');
            // $this->content->text .= html_writer::link($url, $label,array('class' => 'btn btn-secondary'));


            // $mform->addElement('text', 'lo', get_string('selectedlo'), array('size'=>'64','disabled'));
            $mform->addElement('text', 'loid', get_string('selectedloid','goone'), array('size'=>'16','readonly'));
            $mform->setType('loid', PARAM_TEXT);
            $mform->addRule('loid', 'Please use the Content Browser to select GO1 Content', 'required', null, 'client');
            $mform->addElement('text', 'loname', get_string('selectedloname','goone'), array('size'=>'64','readonly'));
            $mform->setType('loname', PARAM_TEXT);
            $mform->addRule('loname', 'Please use the Content Browser to select GO1 Content', 'required', null, 'client');

             $mform->addElement('select', 'popup', get_string('display', 'scorm'),  goone_get_popup_display_array());

            // $options = array();
            // $mform->addElement('selectwithlink', 'scaleid', get_string('scale'), $options, null, 
            //   array('link' => $CFG->wwwroot.'/grade/edit/scale/edit.php?courseid='.$COURSE->id, 'label' => get_string('scalescustomcreate')));
            // $mform->addElement('html', '<div class="form-group row fitem">
            // <div class="col-md-3"></div>
            // <div class="col-md-9 form-inline felement">'.$apiMessage.'</div></div>');

            // $mform->addElement('html', '<div class="form-group row fitem">
            // <div class="col-md-3"><label class="col-form-label d-inline ">Select Lesson</label></div>
            // <div class="col-md-9 form-inline felement">
            // <select class="lesson-list-select" name="lessons[]" multiple="multiple">');
            
            //     foreach ($lesssonList->lessons as $lesson) {
            //         $mform->addElement('html', '<option data-playerid="'. $lesson->playerId .'" data-graceperiod="'. $lesson->gracePeriod .'" data-length="'. $lesson->length .'"  data-desc="'. $lesson->description .'"
            //         data-instructor="'. $lesson->instructor->name .'" value="'. $lesson->id .'">'. $lesson->name .'</option>');
            //     }
            
            //$mform->addElement('html', '</select> </div></div>');
           // $mform->addElement('hidden','lesson_id','');
            // $mform->setType('loid', PARAM_RAW);

            //Edit Page will have the 'update' param, Add page will not have both
            $cmid    = isset($_GET['id']) ? $_GET['id'] : @$_GET['update'];

            // Grade
           // $this->standard_grading_coursemodule_elements();
            
            //-------------------------------------------------------------------------------
            $this->standard_coursemodule_elements();
            //-------------------------------------------------------------------------------

            //Buttons 
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

