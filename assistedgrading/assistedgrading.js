/**
 * This JavaScript code is part of Assisted Grading plugin for Moodle.
 *
 * It mainly performs a sanity check on client side based on
 * data sent by a webservice. Data is available as embedded
 * JavaScript part of the generated output by the plugin.
 *
 * It relies on data being available as global variable 'quiz_data'.
 * It uses CSS provided by the plugin to highlight failed sanity checks.
 *
 * @author Andre Lohan <31loan1bif@hft-stuttgart.de>
 */
jQuery(document).ready(function($) {

    // The id prefix of the element for highlighting sanity check warnings
    var id_prefix = 'quba_';
    // Wait a bit before firing sanity check on user input
    var timer;
    var delay = 1000;

    /**
     * Parses moodle input id and returns quiz attempt id.
     * For example q10:1_-mark returns quiz id 10
     *
     * @param input_id Element id as String
     */
    function get_qubaid_from_input(input_id) {
        var res = input_id.match(/q(\d+):/);
        return res ? res[1] : null;
    }

    /**
     * Cleans up mark input for comparing.
     *
     * @param input
     * @returns String
     */
    function sanitize_input(input) {
        return input.replace(',', '.').replace(' ', '').replace(/0+$/,'');
    }

    /**
     * Returns ids of similar answers for sanity check by looping through quiz_data.
     *
     * @param qubaid
     * @returns List of ids with similar answer
     */
    function get_similar_answers(qubaid) {
        for (var i = 0; i < quiz_data.length; i++){
            if (quiz_data[i].id == qubaid) {
                return typeof quiz_data[i].sanity_check !== 'undefined' ? quiz_data[i].sanity_check : null;
            }
        }
        return null;
    }

    /**
     * Removes visual highlighting of sanity check
     */
    function clear_sanity_check() {
        $('[id^=' + id_prefix + ']').removeClass('sanity_check');
    }

    /**
     * Performs sanity check on similar student answers.
     * The data is available as quiz_data for JavaScript code.
     * The field sanity_check on quiz_data contains ids of similar student answers.
     *
     * @param qubaid
     */
    function sanity_check(qubaid) {
        var points = marks[qubaid];
        var similar_answers = get_similar_answers(qubaid);
        if (similar_answers) {
            console.log('AssistedGrading similar answers: ' + JSON.stringify(similar_answers));

            for (var i = 0; i < similar_answers.length; i++) {
                var similar_answer_id = similar_answers[i];
                // Compare points for similar answer
                console.log('  Similar answer id ' + similar_answer_id + ' points: ' + marks[similar_answer_id]);
                if (typeof marks[similar_answer_id] !== 'undefined' && marks[similar_answer_id] && points != marks[similar_answer_id]) {
                    // Highlight sanity check fail
                    $('#' + id_prefix + qubaid).addClass('sanity_check');
                    $('#' + id_prefix + similar_answer_id).addClass('sanity_check');
                }

            }
        }
    }

    // Bind all user inputs for marks
    $('input[name$="-mark"]').on('input', function() {
        clear_sanity_check();
        var qubaid = get_qubaid_from_input($(this).attr('id'));
        var points = sanitize_input($(this).val());
        marks[qubaid] = points;

        window.clearTimeout(timer);
        if (!points) return;
        timer = window.setTimeout(function(){
            console.log('AssistedGrading input on id ' + qubaid + ' : ' + points);
            sanity_check(qubaid);
        }, delay);
    });

    /**
     * Makes student answers collapsible
     */
    $('.collapsible').on('click', function() {
        var res = $(this).attr('id').match(/collapse_(\d+)/);
        var that = this;
        if (res) {
            var qubaid = res[1];
            console.log('Collapsing ' + qubaid);
            $('#quba_content_' + qubaid).toggle('fast', function() {
                if ($(this).is(':visible')) {
                    $(that).removeClass('collapsed');
                    $(that).addClass('not-collapsed');
                } else {
                    $(that).removeClass('not-collapsed');
                    $(that).addClass('collapsed');
                }
            });
        }
    });
});