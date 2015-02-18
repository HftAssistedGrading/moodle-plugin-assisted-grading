<?php

/**
 * Post-install script for the quiz assisted grading report.
 * @package   quiz_assisted_grading
 * @copyright 2014 HFT Stuttgart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Post-install script
 */
function xmldb_quiz_assistedgrading_install() {
    global $DB;

    $record = new stdClass();
    $record->name         = 'assistedgrading';
    $record->displayorder = '5000';
    $record->capability   = 'mod/quiz:grade';

    $DB->insert_record('quiz_reports', $record);
}