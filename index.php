<?php

    /*
        This page is used by Moodle when listing all the instances of your module 
        that are in a particular course with the course id being passed to this script. 
        The beginning of the page should contain the following -
        You are then free to display the list of instances as you wish.
    */
    require_once('../../config.php');
    
    $id = required_param('id', PARAM_INT);           // Course ID
    
    // Ensure that the course specified is valid
    if (!$course = $DB->get_record('course', array('id'=> $id))) {
        print_error('Course ID is incorrect');
    }