<?php

defined('MOODLE_INTERNAL') || die();

class backup_goone_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $goone = new backup_nested_element('goone', array('id'),
                                            array('name',
                                                  'intro', 
                                                  'introformat',
                                                  'lesson_id',
                                                  'grace_period',
                                                  'completionsubmit',
                                                  'timecreated',
                                                  'timemodified'));

        $lessons = new backup_nested_element('lessons');

        $lesson = new backup_nested_element('lesson', array('id'),
                                            array('lesson_id',
                                                  'lesson_name',
                                                  'lesson_desc',
                                                  'player_id',
                                                  'length',
                                                  'grace_period',
                                                  'instructor',
                                                  'lesson_order',
                                                  'timecreated',
                                                  'timemodified'));

        $lessons_completions = new backup_nested_element('lessons_completions');

        $lessons_completion = new backup_nested_element('lessons_completion', array('id'),
                                            array('user_id',
                                                  'lesson_id',
                                                  'secondswatched',
                                                  'curposition',
                                                  'completed',
                                                  'action',
                                                  'timecreated',
                                                  'timemodified'));

        $completions = new backup_nested_element('completions');

        $completion = new backup_nested_element('completion', array('id'), 
                                                array('user_id',
                                                      'percentage_completed',
                                                      'timecreated',
                                                      'timemodified'));

        $lessons_grades = new backup_nested_element('lessons_grades');

        $lessons_grade = new backup_nested_element('lessons_grade', array('id'),
                                                array('user_id',
                                                      'lesson_id',
                                                      'grade',
                                                      'timecreated',
                                                      'timemodified'));

        
        // Build the tree
        $goone->add_child($lessons);
                $lessons->add_child($lesson);      

                $goone->add_child($lessons_completions);
                $lessons_completions->add_child($lessons_completion);

                $goone->add_child($completions);
                $completions->add_child($completion);

                $goone->add_child($lessons_grades);
                $lessons_grades->add_child($lessons_grade);



        // Define sources
        $goone->set_source_table('goone', array('id' => backup::VAR_ACTIVITYID));
        $lesson->set_source_table('goone_lessons', array('goone_id' => backup::VAR_PARENTID), 'id ASC');

         // All the rest of elements only happen if we are including user info
        if ($userinfo) {
        $lessons_completion->set_source_table('goone_lessons_completion', array('goone_id' => '../../id'));
        $completion->set_source_table('goone_completion', array('goone_id' => '../../id'));
        $lessons_grade->set_source_table('goone_lessons_grade', array('goone_id' => '../../id'));
        }

        // Define id annotations
        $lessons_completion->annotate_ids('user', 'user_id');
        $completion->annotate_ids('user', 'user_id');
        $lessons_grade->annotate_ids('user', 'user_id');


        // Define file annotations
        // (none)

        // Return the root element (goone), wrapped into standard activity structure
        return $this->prepare_activity_structure($goone);
    }

}

