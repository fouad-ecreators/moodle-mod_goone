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

        if ($userinfo) {    
            $paths[] = new restore_path_element('goone_completion', '/activity/goone/goone_completions/goone_completion');
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

    protected function process_goone_completion($data) {
        global $DB;

        $data = (object)$data;

        $data->goone_id = $this->get_new_parentid('goone');
        $data->user_id = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('goone_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }    

    protected function after_execute() {
        // Add goone related files, no need to match by itemname (just internally handled context)
    }
}
