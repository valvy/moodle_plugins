<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'mod/coder:addinstance' => array(
        'riskbitmask' => RISK_XSS,
        'captype'     => 'write',
        'contextlevel'=> CONTEXT_MODULE,
        'archetypes'  => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/coder:view' => array(
        'captype'     => 'read',
        'contextlevel'=> CONTEXT_MODULE,
        'archetypes'  => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    )
);
