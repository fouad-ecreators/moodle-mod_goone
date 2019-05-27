<?php

    /*
    This is where you define what capabilities your plugin will create. 
    Note, if you add new capabilities to this file after your plugin has 
    been installed you will need to increase the version number in your 
    version.php file (discussed later) in order for them to be installed.
    */

    $capabilities = array(
 
        'mod/goone:addinstance' => array(
            'riskbitmask' => RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'moodle/course:manageactivities'
        ),
        'mod/goone:view' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        )


    );

