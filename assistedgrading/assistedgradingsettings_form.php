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
 * This file defines the setting form for the quiz grading report.
 *
 * @package   quiz_grading
 * @copyright 2010 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Quiz grading report settings form.
 *
 * @copyright 2010 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_assistedgrading_settings_form extends moodleform {
    
    protected $includeauto;
    protected $hidden = array();
    protected $counts;
    protected $shownames;
    protected $showidnumbers;
    protected $wsaddress;
    protected $scororder;

    public function __construct($hidden, $counts, $shownames, $showidnumbers, $wsaddress) {
        global $CFG;
        $this->includeauto = !empty($hidden['includeauto']);
        $this->hidden = $hidden;
        $this->counts = $counts;
        $this->shownames = $shownames;
        $this->showidnumbers = $showidnumbers;
        $this->wsaddress = $wsaddress;
        parent::__construct($CFG->wwwroot . '/mod/quiz/report.php', null, 'get');
    }

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'options', get_string('options', 'quiz_grading'));

        $gradeoptions = array();
        foreach (array('needsgrading', 'manuallygraded', 'autograded', 'all') as $type) {
            if (empty($this->counts->$type)) {
                continue;
            }
            if ($type == 'autograded' && !$this->includeauto) {
                continue;
            }
            $gradeoptions[$type] = get_string('gradeattempts' . $type, 'quiz_grading',
                    $this->counts->$type);
        }
        $mform->addElement('select', 'grade', get_string('attemptstograde', 'quiz_grading'),
                $gradeoptions);

        $orderoptions = array(
            'random' => get_string('randomly', 'quiz_grading'),
            'date' => get_string('bydate', 'quiz_grading'),
            'score' => get_string('byscore', 'quiz_assistedgrading'),
        );
        if ($this->shownames) {
            $orderoptions['studentfirstname'] = get_string('bystudentfirstname', 'quiz_grading');
            $orderoptions['studentlastname']  = get_string('bystudentlastname', 'quiz_grading');
        }
        if ($this->showidnumbers) {
            $orderoptions['idnumber'] = get_string('bystudentidnumber', 'quiz_grading');
        }
        // Temporary disabled due to sorting by score only
//        $mform->addElement('select', 'order', get_string('orderattempts', 'quiz_grading'),
//                $orderoptions);

        foreach ($this->hidden as $name => $value) {
            $mform->addElement('hidden', $name, $value);
            if ($name == 'mode') {
                $mform->setType($name, PARAM_ALPHA);
            } else {
                $mform->setType($name, PARAM_INT);
            }
        }
        
        $mform->addElement('text', 'wsaddress', get_string('wsaddress', 'quiz_assistedgrading'));
        $mform->setType('wsaddress', PARAM_NOTAGS);

        $scoreorderoptions = array(
            'desc' => get_string('byscoredesc', 'quiz_assistedgrading'),
            'asc' => get_string('byscoreasc', 'quiz_assistedgrading'),
            'rand' => get_string('byscorerand', 'quiz_assistedgrading'),
        );
        $mform->addElement('select', 'scoreorder', get_string('orderby', 'quiz_assistedgrading'), $scoreorderoptions);

        $mform->addElement('submit', 'submitbutton', get_string('changeoptions', 'quiz_grading'));
    }
}