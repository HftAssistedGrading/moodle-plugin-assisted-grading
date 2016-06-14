<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'quiz_assisted_grading', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   quiz_assisted_grading
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Assisted grading';
$string['assistedgrading'] = 'Assisted grading';
$string['wsaddress'] = 'Webservice URL';
$string['wsavail'] = 'Webservice available';
$string['wsunavail'] = 'Webservice not available';
$string['curlnotfound'] = 'cURL extension not found';
$string['byscore'] = 'By score';
$string['addanswer'] = 'Add answer to reference answer';
$string['save'] = 'Save';
$string['byscoredesc'] = 'By score descending';
$string['byscoreasc'] = 'By score ascending';
$string['bystudentfirstname'] = 'By student first name';
$string['bystudentlastname'] = 'By student last name';
$string['gradingattemptsxtoyofz'] = 'Grading attempts {$a->from} to {$a->to} of {$a->of}';
$string['random'] = 'Random';
$string['orderby'] = 'Sort by';
$string['bymark'] = 'By mark';
$string['nojs'] = 'JavaScript is not available but is required for full functionality of the module!';
$string['languageoptions'] = 'Language options';
$string['English'] = 'en';
$string['German'] = 'de';  
$string['orderattempts'] = 'Order attempts';
$string['threshold'] = 'Threshold for consistency check';
$string['threshold_help'] = 'Enter a threshold value between 0.0 (indicating no overlap) and 1.0 (indicating complete overlap). A high threshold value means that fewer answers will be considered to be similar.';
$string['threshold_error'] = 'The entered value must be a value between 0.0 and 1.0';