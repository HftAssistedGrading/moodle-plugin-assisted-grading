<?php

/**
 * This file defines the quiz assisted grading report class.
 * Based on quiz report.
 * 
 * @package   quiz_assistedgrading
 * @copyright 2015 HFT Stuttgart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/assistedgrading/assistedgradingsettings_form.php');

/**
 * Quiz report to help teachers manually grade questions that need it.
 *
 * This report basically provides two screens:
 * - List question that might need manual grading (or optionally all questions).
 * - Provide an efficient UI to grade all attempts at a particular question.
 *
 * @copyright 2006 Gustav Delius
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_assistedgrading_report extends quiz_default_report {

    /** @const Used for storing and retrieving plugin configuration. */
    const PLUGIN = 'quiz_assistedgrading';
    
    const DEFAULT_PAGE_SIZE = -1;
    const DEFAULT_ORDER = 'random';
    
    /** @const Webservice default settings. */
    const WS_BASE_ADDRESS = 'http://193.196.143.147:8080/GA/webresources/gradingassistant';
    const WS_POST_ADDRESS = '/post';
    const WS_PING_ADDRESS = '/ping';

    // Dummy webservice for testing
    //const WS_BASE_ADDRESS = 'http://moodle.localhost';
    //const WS_POST_ADDRESS = '/ws.php'; // Simple script that generates random score number
    //const WS_PING_ADDRESS = '/ws_ping.php'; // Just returns true

    protected $viewoptions = array();
    protected $questions;
    protected $cm;
    protected $quiz;
    protected $context;
    protected $addanswers;

    public function display($quiz, $cm, $course) {
        global $CFG, $DB, $PAGE;

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;
        
        // add answers come as comma separated list in url due to internal redirect
        $addanswers = optional_param('addanswers', null, PARAM_TEXT);
        if ($addanswers) {
            $this->addanswers = explode(',', $addanswers);
        }
        
        // Get the URL options.
        $slot = optional_param('slot', null, PARAM_INT);
        $questionid = optional_param('qid', null, PARAM_INT);
        $grade = optional_param('grade', null, PARAM_ALPHA);

        $includeauto = optional_param('includeauto', false, PARAM_BOOL);
        if (!in_array($grade, array('all', 'needsgrading', 'autograded', 'manuallygraded'))) {
            $grade = null;
        }
        $pagesize = optional_param('pagesize', self::DEFAULT_PAGE_SIZE, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $order = optional_param('order', self::DEFAULT_ORDER, PARAM_ALPHA);
        $wsaddress_cfg = get_config(self::PLUGIN, 'wsaddress');
        $wsaddress = optional_param(
                'wsaddress', 
                $wsaddress_cfg !== FALSE ? $wsaddress_cfg : self::WS_BASE_ADDRESS, 
                PARAM_TEXT
                );
        // Store wsaddress setting if required
        if ($wsaddress != $wsaddress_cfg) {
            set_config('wsaddress', $wsaddress, self::PLUGIN);
        }
        
        // Assemble the options requried to reload this page.
        $optparams = array('includeauto', 'page');
        foreach ($optparams as $param) {
            if ($$param) {
                $this->viewoptions[$param] = $$param;
            }
        }
        if ($pagesize != self::DEFAULT_PAGE_SIZE) {
            $this->viewoptions['pagesize'] = $pagesize;
        }
        if ($order != self::DEFAULT_ORDER) {
            $this->viewoptions['order'] = $order;
        }
        if ($wsaddress != self::WS_POST_ADDRESS) {
            $this->viewoptions['wsaddress'] = $wsaddress;
        }

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);
        $shownames = has_capability('quiz/grading:viewstudentnames', $this->context);
        $showidnumbers = has_capability('quiz/grading:viewidnumber', $this->context);

        // Validate order.
        if (!in_array($order, array('random', 'date', 'studentfirstname', 'studentlastname', 'idnumber', 'score'))) {
            $order = self::DEFAULT_ORDER;
        } else if (!$shownames && ($order == 'studentfirstname' || $order == 'studentlastname')) {
            $order = self::DEFAULT_ORDER;
        } else if (!$showidnumbers && $order == 'idnumber') {
            $order = self::DEFAULT_ORDER;
        }
        if ($order == 'random') {
            $page = 0;
        }

        // Get the list of questions in this quiz.
        $this->questions = quiz_report_get_significant_questions($quiz);

        if ($slot && !array_key_exists($slot, $this->questions)) {
            throw new moodle_exception('unknownquestion', 'quiz_grading');
        }

        
        // Process any submitted data.
        if ($data = data_submitted() && confirm_sesskey() && $this->validate_submitted_marks()) {
            $this->process_submitted_data();
            // selected student answers get lost on redirect, put those in url
            $addanswers = optional_param_array('addanswer', null, PARAM_INT);
            redirect($this->grade_question_url($slot, $questionid, $grade, $page + 1, $addanswers));
        }

        // Get the group, and the list of significant users.
        $this->currentgroup = $this->get_current_group($cm, $course, $this->context);
        if ($this->currentgroup == self::NO_GROUPS_ALLOWED) {
            $this->users = array();
        } else {
            $this->users = get_users_by_capability($this->context, array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), '', '', '', '', $this->currentgroup, '', false);
        }

        $hasquestions = quiz_has_questions($quiz->id);
        $counts = null;
        if ($slot && $hasquestions) {
            // Make sure there is something to do.
            $statecounts = $this->get_question_state_summary(array($slot));
            foreach ($statecounts as $record) {
                if ($record->questionid == $questionid) {
                    $counts = $record;
                    break;
                }
            }
            // If not, redirect back to the list.
            if (!$counts || $counts->$grade == 0) {
                redirect($this->list_questions_url(), get_string('alldoneredirecting', 'quiz_grading'));
            }
        }

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'grading');

        // What sort of page to display?
        if (!$hasquestions) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else if (!$slot) {
            $this->display_index();
        } else {
            $this->display_grading_interface($slot, $questionid, $grade, $pagesize, $page, $shownames, $showidnumbers, $order, $counts, $wsaddress);
        }
        return true;
    }

    protected function get_qubaids_condition() {
        global $DB;

        $where = "quiza.quiz = :mangrquizid AND
                quiza.preview = 0 AND
                quiza.state = :statefinished";
        $params = array('mangrquizid' => $this->cm->instance, 'statefinished' => quiz_attempt::FINISHED);

        $currentgroup = groups_get_activity_group($this->cm, true);
        if ($currentgroup) {
            $users = get_users_by_capability($this->context, array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), 'u.id, u.id', '', '', '', $currentgroup, '', false);
            if (empty($users)) {
                $where .= ' AND quiza.userid = 0';
            } else {
                list($usql, $uparam) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'mangru');
                $where .= ' AND quiza.userid ' . $usql;
                $params += $uparam;
            }
        }

        return new qubaid_join('{quiz_attempts} quiza', 'quiza.uniqueid', $where, $params);
    }

    protected function load_attempts_by_usage_ids($qubaids) {
        global $DB;

        list($asql, $params) = $DB->get_in_or_equal($qubaids);
        $params[] = quiz_attempt::FINISHED;
        $params[] = $this->quiz->id;

        $fields = 'quiza.*, u.idnumber, ';
        $fields .= get_all_user_name_fields(true, 'u');
        $attemptsbyid = $DB->get_records_sql("
                SELECT $fields
                FROM {quiz_attempts} quiza
                JOIN {user} u ON u.id = quiza.userid
                WHERE quiza.uniqueid $asql AND quiza.state = ? AND quiza.quiz = ?", $params);

        $attempts = array();
        foreach ($attemptsbyid as $attempt) {
            $attempts[$attempt->uniqueid] = $attempt;
        }
        return $attempts;
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @param $includeauto if not given, use the current setting, otherwise,
     *      force a paricular value of includeauto in the URL.
     * @return string the URL.
     */
    protected function base_url() {
        return new moodle_url('/mod/quiz/report.php', array('id' => $this->cm->id, 'mode' => 'assistedgrading'));
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @param $includeauto if not given, use the current setting, otherwise,
     *      force a paricular value of includeauto in the URL.
     * @return string the URL.
     */
    protected function list_questions_url($includeauto = null) {
        $url = $this->base_url();

        $url->params($this->viewoptions);

        if (!is_null($includeauto)) {
            $url->param('includeauto', $includeauto);
        }

        return $url;
    }

    /**
     * @param int $slot
     * @param int $questionid
     * @param string $grade
     * @param mixed $page = true, link to current page. false = omit page.
     *      number = link to specific page.
     */
    protected function grade_question_url($slot, $questionid, $grade, $page = true, $addanswers = null) {
        $url = $this->base_url();
        $url->params(array('slot' => $slot, 'qid' => $questionid, 'grade' => $grade));
        if ($addanswers) {
            $url->params(array('addanswers' => implode(',', $addanswers)));
        }
        $url->params($this->viewoptions);

        $options = $this->viewoptions;
        if (!$page) {
            $url->remove_params('page');
        } else if (is_integer($page)) {
            $url->param('page', $page);
        }

        return $url;
    }

    protected function format_count_for_table($counts, $type, $gradestring) {
        $result = $counts->$type;
        if ($counts->$type > 0) {
            $result .= ' ' . html_writer::link($this->grade_question_url(
                                    $counts->slot, $counts->questionid, $type), get_string($gradestring, 'quiz_grading'), array('class' => 'gradetheselink'));
        }
        return $result;
    }

    protected function display_index($includeauto) {
        global $OUTPUT;

        if ($groupmode = groups_get_activity_groupmode($this->cm)) {
            // Groups is being used.
            groups_print_activity_menu($this->cm, $this->list_questions_url());
        }

        echo $OUTPUT->heading(get_string('questionsthatneedgrading', 'quiz_grading'), 3);

        $statecounts = $this->get_question_state_summary(array_keys($this->questions));

        $data = array();
        foreach ($statecounts as $counts) {
            if ($counts->all == 0) {
                continue;
            }
            if (!$includeauto && $counts->needsgrading == 0 && $counts->manuallygraded == 0) {
                continue;
            }

            $row = array();

            $row[] = $this->questions[$counts->slot]->number;

            $row[] = format_string($counts->name);

            $row[] = $this->format_count_for_table($counts, 'needsgrading', 'grade');

            $row[] = $this->format_count_for_table($counts, 'manuallygraded', 'updategrade');

            if ($includeauto) {
                $row[] = $this->format_count_for_table($counts, 'autograded', 'updategrade');
            }

            $row[] = $this->format_count_for_table($counts, 'all', 'gradeall');

            $data[] = $row;
        }

        if (empty($data)) {
            echo $OUTPUT->notification(get_string('nothingfound', 'quiz_grading'));
            return;
        }

        $table = new html_table();
        $table->class = 'generaltable';
        $table->id = 'questionstograde';

        $table->head[] = get_string('qno', 'quiz_grading');
        $table->head[] = get_string('questionname', 'quiz_grading');
        $table->head[] = get_string('tograde', 'quiz_grading');
        $table->head[] = get_string('alreadygraded', 'quiz_grading');
        if ($includeauto) {
            $table->head[] = get_string('automaticallygraded', 'quiz_grading');
        }
        $table->head[] = get_string('total', 'quiz_grading');

        $table->data = $data;
        echo html_writer::table($table);
    }

    /**
     * Webservice post via cURL.
     * 
     * @global object $OUTPUT
     * @param string $wsaddress
     * @param string $message
     * @param string $contentType
     * @return string
     */
    protected function ws_post($wsaddress, $message, $contentType = 'application/json') {
        global $OUTPUT;
        if (!in_array('curl', get_loaded_extensions())) {
            echo $OUTPUT->notification(get_string('curlnotfound', 'quiz_assistedgrading'));
            return false;
        }
        $ch = curl_init();

        $headers = array(
            'Content-Type: ' . $contentType,
        );

        curl_setopt($ch, CURLOPT_URL, $wsaddress);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $debug = '';

        $debug .= 'cURL CURLOPT_URL: ' . $wsaddress . '<br/>';
        $debug .= 'cURL status: ' . $status . '<br/>';
        $debug .= 'cURL content type: ' . $content_type . '<br/>';
        $debug .= 'WS reply: ';
        $debug .= print_r($result, true);

        //echo $OUTPUT->notification($debug); // For debuging print cURL details
        return $result;
    }

    /**
     * Performs availability check for webservice.
     * 
     * The webservice is expected to provide a ping method that just returns
     * true to see if the webservice is available and responding before
     * sending data to it.
     * 
     * @param String $wsaddress
     * @return true or false
     */
    protected function check_ws_availability($wsaddress) {
        global $OUTPUT;
        if (!in_array('curl', get_loaded_extensions())) {
            echo $OUTPUT->notification(get_string('curlnotfound', 'quiz_assistedgrading'));
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wsaddress);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // else response is printed
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status_code == 200 && $result == 'true') {
            return true;
        }
        echo $OUTPUT->notification(get_string('wsunavail', 'quiz_assistedgrading'));
        return false;
    }

    /**
     * Used for usort to sort question attempts by descending score.
     * 
     * @param Array $a
     * @param Array $b
     * @return Sorted array by score
     */
    protected function sort_by_score($a, $b) {
        if ($a['score'] == $b['score']) {
            return 0;
        }
        return ($a['score'] > $b['score']) ? -1 : 1;
    }

    protected function display_grading_interface($slot, $questionid, $grade, $pagesize, $page, $shownames, $showidnumbers, $order, $counts, $wsaddress) {
        global $OUTPUT;

        if ($pagesize * $page >= $counts->$grade) {
            $page = 0;
        }
        // disable paging for now
        $page = 0;
        $pagesize = null;

        list($qubaids, $count) = $this->get_usage_ids_where_question_in_state(
                $grade, $slot, $questionid, $order, $page, $pagesize);
        $attempts = $this->load_attempts_by_usage_ids($qubaids);

        // Prepare the form.
        $hidden = array(
            'id' => $this->cm->id,
            'mode' => 'assistedgrading',
            'slot' => $slot,
            'qid' => $questionid,
            'page' => $page,
        );
        if (array_key_exists('includeauto', $this->viewoptions)) {
            $hidden['includeauto'] = $this->viewoptions['includeauto'];
        }
        $mform = new quiz_assistedgrading_settings_form($hidden, $counts, $shownames, $showidnumbers, $wsaddress);

        // Tell the form the current settings.
        $settings = new stdClass();
        $settings->grade = $grade;
        $settings->pagesize = $pagesize;
        $settings->order = $order;
        $settings->wsaddress = $wsaddress;
        $mform->set_data($settings);

        // Print the heading and form.
        echo question_engine::initialise_js();

        $a = new stdClass();
        $a->number = $this->questions[$slot]->number;
        $a->questionname = format_string($counts->name);
        echo $OUTPUT->heading(get_string('gradingquestionx', 'quiz_grading', $a), 3);
        echo html_writer::tag('p', html_writer::link($this->list_questions_url(), get_string('backtothelistofquestions', 'quiz_grading')), array('class' => 'mdl-align'));

        $mform->display();

        // Check if webservice is available before sending data
        if (!$this->check_ws_availability($wsaddress . self::WS_PING_ADDRESS)) {
            return;
        }

        // Paging info.
        $a = new stdClass();
        $a->from = $page * $pagesize + 1;
        $a->to = min(($page + 1) * $pagesize, $count);
        $a->of = $count;
        echo $OUTPUT->heading(get_string('gradingattemptsxtoyofz', 'quiz_grading', $a), 3);

        if ($count > $pagesize && $order != 'random') {
            echo $OUTPUT->paging_bar($count, $page, $pagesize, $this->grade_question_url($slot, $questionid, $grade, false));
        }

        // Display the form with one section for each attempt.
        $sesskey = sesskey();
        $qubaidlist = implode(',', $qubaids);
        echo html_writer::start_tag('form', array('method' => 'post',
            'action' => $this->grade_question_url($slot, $questionid, $grade, $page),
            'class' => 'mform', 'id' => 'manualgradingform')) .
        html_writer::start_tag('div') .
        html_writer::input_hidden_params(new moodle_url('', array(
            'qubaids' => $qubaidlist, 'slots' => $slot, 'sesskey' => $sesskey)));

        // Compile list for webservice
        $wsdata = array();
        $records = array();
        
        // This will be filled by selected students answers
        $add_to_referenceanswer = '';
        
        foreach ($qubaids as $qubaid) {
            $record = array();
            $attempt = $attempts[$qubaid];
            //print_r($attempt);
            $quba = question_engine::load_questions_usage_by_activity($qubaid);
            //print_r($quba->get_question_attempt($slot));
            $question = $quba->get_question($slot);
            //print_r($question);
            //print_r($attempt);
            //print_r($quba);
            $record['id'] = intval($quba->get_id());
            //$record['id'] = $attempt->id;
            //$record['question'] = $question->get_question_summary();
            $record['question'] = str_replace("\n", ' ', $quba->get_question_summary($slot));
            $record['referenceanswer'] = str_replace("\n", ' ', $question->graderinfo);
            $record['answer'] = str_replace("\n", ' ', $quba->get_response_summary($slot));
            
            // Append students answer to reference
            if (is_array($this->addanswers) && in_array($quba->get_id(), $this->addanswers)) {
                // Adding student answer to reference answer
                $add_to_referenceanswer .= "\n".$record['answer'];
            }
            
            // Temp test
            //$myqa = $quba->get_question_attempt($slot);
            
            //$record['rightanswer'] = $quba->get_right_answer_summary($slot);
            $record['max'] = $question->defaultmark;
            $record['numAttempts'] = intval($attempt->attempt);
            $record['min'] = floor(($attempt->timefinish - $attempt->timestart) / 60);
            $record['sec'] = ($attempt->timefinish - $attempt->timestart) % 60;

            $records[] = $record;
        }
        
        // After collecting student answers marked for adding to reference answer, inject them on all records
        if ($add_to_referenceanswer) {
            foreach ($records as $key => $record) {
                $record['referenceanswer'] .= $add_to_referenceanswer;
                $records[$key] = $record;
            }
        }
        
        $wsdata['records'] = $records;
        
        // for debug purposes show request to webservice before posting
        echo "<!-- Request JSON: \n";
        echo json_encode($wsdata);
        echo "\n-->\n";
        $ws_result = $this->ws_post($wsaddress . self::WS_POST_ADDRESS, $wsdata);
        echo "\n\n<!-- Response from webservice:\n";
        print_r($ws_result);
        echo "\n-->\n";

        $json_reply = json_decode($ws_result, true);

        if (!is_array($json_reply)) {
            // Something went wrong with webservice reply
            echo $OUTPUT->notification("Could not parse webservice reply.");
        }

        // sort attempts by score
        usort($json_reply, array($this, 'sort_by_score'));
        //echo "result after sorting:\n";
        //print_r($json_reply);

        //if ($order === 'score') { // Not working yet due to forwarding order to database layer
        if (true) {
            $qubaids = array();
            // compile new qubaidslist based on sorted score
            foreach ($json_reply as $json_item) {
                $qubaids[] = $json_item['id'];
            }
        }

        foreach ($qubaids as $qubaid) {
            $attempt = $attempts[$qubaid];
            $quba = question_engine::load_questions_usage_by_activity($qubaid);

            // Inject modified answer from webservice

            $displayoptions = quiz_get_review_options($this->quiz, $attempt, $this->context);
            $displayoptions->hide_all_feedback();
            $displayoptions->history = question_display_options::HIDDEN;
            $displayoptions->manualcomment = question_display_options::EDITABLE;

            
            $heading = $this->get_question_heading($attempt, $shownames, $showidnumbers);
            if ($heading) {
                echo $OUTPUT->heading($heading, 4);
            }
            echo html_writer::checkbox("addanswer[]", $quba->get_id(), 
                    (is_array($this->addanswers) && in_array($quba->get_id(), $this->addanswers)), 
                    get_string('addanswer', 'quiz_assistedgrading'));
            // Find answer from webservice
            $resp = null;
            foreach ($json_reply as $rep) {
                if ($rep['id'] == $quba->get_id()) {
                    $resp = $rep;
                }
            }
            //if ($resp !== null) {
            //echo html_writer::tag('div', '[Score: ' . $resp['score'] . '] ' . $resp['answer'],
              //  array('class' => 'alert qtype_essay_response readonly'));
            //}
            echo $quba->render_question($slot, $displayoptions, $this->questions[$slot]->number);
            
        }

        echo html_writer::tag('div', html_writer::empty_tag('input', array(
                    'type' => 'submit', 'value' => get_string('save', 'quiz_assistedgrading'))), array('class' => 'mdl-align')) .
        html_writer::end_tag('div') . html_writer::end_tag('form');
    }

    protected function get_question_heading($attempt, $shownames, $showidnumbers) {
        $a = new stdClass();
        $a->attempt = $attempt->attempt;
        $a->fullname = fullname($attempt);
        $a->idnumber = $attempt->idnumber;

        $showidnumbers &=!empty($attempt->idnumber);

        if ($shownames && $showidnumbers) {
            return get_string('gradingattemptwithidnumber', 'quiz_grading', $a);
        } else if ($shownames) {
            return get_string('gradingattempt', 'quiz_grading', $a);
        } else if ($showidnumbers) {
            $a->fullname = $attempt->idnumber;
            return get_string('gradingattempt', 'quiz_grading', $a);
        } else {
            return '';
        }
    }

    protected function validate_submitted_marks() {

        $qubaids = optional_param('qubaids', null, PARAM_SEQUENCE);
        if (!$qubaids) {
            return false;
        }
        $qubaids = clean_param_array(explode(',', $qubaids), PARAM_INT);

        $slots = optional_param('slots', '', PARAM_SEQUENCE);
        if (!$slots) {
            $slots = array();
        } else {
            $slots = explode(',', $slots);
        }

        foreach ($qubaids as $qubaid) {
            foreach ($slots as $slot) {
                if (!question_engine::is_manual_grade_in_range($qubaid, $slot)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function process_submitted_data() {
        global $DB;

        $qubaids = optional_param('qubaids', null, PARAM_SEQUENCE);
        if (!$qubaids) {
            return;
        }

        $qubaids = clean_param_array(explode(',', $qubaids), PARAM_INT);
        $attempts = $this->load_attempts_by_usage_ids($qubaids);

        $transaction = $DB->start_delegated_transaction();
        foreach ($qubaids as $qubaid) {
            $attempt = $attempts[$qubaid];
            $attemptobj = new quiz_attempt($attempt, $this->quiz, $this->cm, $this->course);
            $attemptobj->process_submitted_actions(time());
        }
        $transaction->allow_commit();
    }

    /**
     * Load information about the number of attempts at various questions in each
     * summarystate.
     *
     * The results are returned as an two dimensional array $qubaid => $slot => $dataobject
     *
     * @param array $slots A list of slots for the questions you want to konw about.
     * @return array The array keys are slot,qestionid. The values are objects with
     * fields $slot, $questionid, $inprogress, $name, $needsgrading, $autograded,
     * $manuallygraded and $all.
     */
    protected function get_question_state_summary($slots) {
        $dm = new question_engine_data_mapper();
        return $dm->load_questions_usages_question_state_summary(
                        $this->get_qubaids_condition(), $slots);
    }

    /**
     * Get a list of usage ids where the question with slot $slot, and optionally
     * also with question id $questionid, is in summary state $summarystate. Also
     * return the total count of such states.
     *
     * Only a subset of the ids can be returned by using $orderby, $limitfrom and
     * $limitnum. A special value 'random' can be passed as $orderby, in which case
     * $limitfrom is ignored.
     *
     * @param int $slot The slot for the questions you want to konw about.
     * @param int $questionid (optional) Only return attempts that were of this specific question.
     * @param string $summarystate 'all', 'needsgrading', 'autograded' or 'manuallygraded'.
     * @param string $orderby 'random', 'date', 'student' or 'idnumber'.
     * @param int $page implements paging of the results.
     *      Ignored if $orderby = random or $pagesize is null.
     * @param int $pagesize implements paging of the results. null = all.
     */
    protected function get_usage_ids_where_question_in_state($summarystate, $slot, $questionid = null, $orderby = 'random', $page = 0, $pagesize = null) {
        global $CFG, $DB;
        $dm = new question_engine_data_mapper();

        if ($pagesize && $orderby != 'random') {
            $limitfrom = $page * $pagesize;
        } else {
            $limitfrom = 0;
        }

        $qubaids = $this->get_qubaids_condition();

        $params = array();
        if ($orderby == 'date') {
            list($statetest, $params) = $dm->in_summary_state_test(
                    'manuallygraded', false, 'mangrstate');
            $orderby = "(
                    SELECT MAX(sortqas.timecreated)
                    FROM {question_attempt_steps} sortqas
                    WHERE sortqas.questionattemptid = qa.id
                        AND sortqas.state $statetest
                    )";
        } else if ($orderby == 'studentfirstname' || $orderby == 'studentlastname' || $orderby == 'idnumber') {
            $qubaids->from .= " JOIN {user} u ON quiza.userid = u.id ";
            // For name sorting, map orderby form value to
            // actual column names; 'idnumber' maps naturally
            switch ($orderby) {
                case "studentlastname":
                    $orderby = "u.lastname, u.firstname";
                    break;
                case "studentfirstname":
                    $orderby = "u.firstname, u.lastname";
                    break;
            }
        }

        return $dm->load_questions_usages_where_question_in_state($qubaids, $summarystate, $slot, $questionid, $orderby, $params, $limitfrom, $pagesize);
    }

}
