<?php

defined('MOODLE_INTERNAL') || die();

class backup_goone_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $goone = new backup_nested_element('goone', array('id'),
                                            array('name',
                                                  'loid',
                                                  'loname',
                                                  'token',
                                                  'completionsubmit',
                                                  'popup',
                                                  'timecreated',
                                                  'timemodified'));


        $goone_completions = new backup_nested_element('goone_completions');

        $goone_completion = new backup_nested_element('goone_completion', array('id'),
                                            array('gooneid',
                                                  'userid',
                                                  'location',
                                                  'completed'));
                // Build the tree
                $goone->add_child($goone_completions);
                $goone_completions->add_child($goone_completion);



        // Define sources
        $goone->set_source_table('goone', array('id' => backup::VAR_ACTIVITYID));

         // All the rest of elements only happen if we are including user info
        if ($userinfo) {
        $goone_completion->set_source_table('goone_completion', array('gooneid' => '../../id'));
        }

        // Define id annotations
        $goone_completion->annotate_ids('user', 'userid');


        // Define file annotations
        // (none)

        // Return the root element (goone), wrapped into standard activity structure
        return $this->prepare_activity_structure($goone);
    }

}

