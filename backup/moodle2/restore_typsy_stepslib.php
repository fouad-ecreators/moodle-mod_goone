<?php

/**
 * Define all the restore steps that will be used by the restore_goone_activity_task
 */

/**
 * Structure step to restore one goone activity
 */
class restore_goone_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('goone', '/activity/goone');
        $paths[] = new restore_path_element('goone_lesson', '/activity/goone/lessons/lesson');

        if ($userinfo) {    
            $paths[] = new restore_path_element('goone_completion', '/activity/goone/completions/completion');
            $paths[] = new restore_path_element('goone_lessons_grade', '/activity/goone/lessons_grades/lessons_grade');
            $paths[] = new restore_path_element('goone_lessons_completion', '/activity/goone/lessons_completions/lessons_completion');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_goone($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // insert the goone record
        $newitemid = $DB->insert_record('goone', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

   protected function process_goone_lesson($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->goone_id = $this->get_new_parentid('goone');
        $newitemid = $DB->insert_record('goone_lessons', $data);
        $this->set_mapping('goone_lesson', $oldid, $newitemid);
    }

    protected function process_goone_lessons_completion($data) {
        global $DB;

        $data = (object)$data;

        $data->goone_id = $this->get_new_parentid('goone');
        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $newitemid = $DB->insert_record('goone_lessons_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }    

    protected function process_goone_completion($data) {
        global $DB;

        $data = (object)$data;

        $data->goone_id = $this->get_new_parentid('goone');
        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $newitemid = $DB->insert_record('goone_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }    

    protected function process_goone_lessons_grade($data) {
        global $DB;

        $data = (object)$data;

        $data->goone_id = $this->get_new_parentid('goone');
        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $newitemid = $DB->insert_record('goone_lessons_grade', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add goone related files, no need to match by itemname (just internally handled context)
    }
}
