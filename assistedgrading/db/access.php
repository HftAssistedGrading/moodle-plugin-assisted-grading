<?php

/**
 * Capability definitions for the quiz assisted grading report.
 *
 * @package   quiz_assisted_grading
 * @copyright 2014 HFT Stuttgart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    // Is the user allowed to see the student's real names while grading?
    'quiz/assistedgrading:viewstudentnames' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ),
        'clonepermissionsfrom' =>  'mod/quiz:viewreports'
    ),

    // Is the user allowed to see the student's idnumber while grading?
    'quiz/assistedgrading:viewidnumber' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ),
        'clonepermissionsfrom' =>  'mod/quiz:viewreports'
    )
);