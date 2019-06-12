<?php

defined('MOODLE_INTERNAL') || die();
 
require_once($CFG->dirroot . '/mod/goone/backup/moodle2/backup_goone_stepslib.php'); // Because it exists (must)
 
/**
 * goone backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_goone_activity_task extends backup_activity_task {
 
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
 
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_goone_activity_structure_step('goone_structure', 'goone.xml'));
        // Choice only has one structure step
    }
 
    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;
 
        $base = preg_quote($CFG->wwwroot,"/");
 
        // Link to the list of goone
        $search="/(".$base."\/mod\/goone\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@gooneINDEX*$2@$', $content);
 
        // Link to goone view by moduleid
        $search="/(".$base."\/mod\/goone\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@gooneVIEWBYID*$2@$', $content);
 
        return $content;
    }
}