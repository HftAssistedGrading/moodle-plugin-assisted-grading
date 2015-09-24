jQuery(document).ready(function($) {

    // The id prefix of element for highlighting sanity check warnings
    var id_prefix = 'quba_';
    // Wait a bit before firing sanity check on user input
    var timer;
    var delay = 1000;
    // Cache points for each id
    var points_by_id = [];

    /**
     * Parses moodle input id and returns quiz attempt id.
     * For example q10:1_-mark returns quiz id 10
     *
     * @param $input
     */
    function get_qubaid_from_input(input_id) {
        var res = input_id.match(/q(\d+):/);
        return res ? res[1] : null;
    }

    /**
     * Cleans up mark input for comparing.
     *
     * @param input
     * @returns {*}
     */
    function sanitize_input(input) {
        return input.replace(',', '.').replace(' ', '');
    }

    /**
     * Returns ids of similar answers for sanity check by looping through quiz_data.
     *
     * @param qubaid
     * @returns {*}
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
     * Preforms sanity check on similar student answers.
     * The data is available as quiz_data for JavaScript code.
     * The field sanity_check on quiz_data contains ids of similar student answers.
     *
     * @param qubaid
     */
    function sanity_check(qubaid) {
        var points = points_by_id[qubaid];
        var similar_answers = get_similar_answers(qubaid);
        if (similar_answers) {
            console.log('AssistedGrading similar answers: ' + JSON.stringify(similar_answers));

            for (var i = 0; i < similar_answers.length; i++) {
                var similar_answer_id = similar_answers[i];
                // Compare points for similar answer
                console.log('  Similar answer id ' + similar_answer_id + ' points: ' + points_by_id[similar_answer_id]);
                if (typeof points_by_id[similar_answer_id] !== 'undefined' && points != points_by_id[similar_answer_id]) {
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
        var points = $(this).val();
        points_by_id[qubaid] = sanitize_input(points);

        window.clearTimeout(timer);
        timer = window.setTimeout(function(){
            console.log('AssistedGrading input on id ' + qubaid + ' : ' + points);
            sanity_check(qubaid);
        }, delay);
    });
});