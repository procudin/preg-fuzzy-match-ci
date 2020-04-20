<?php
// This file is part of WriteRegex question type - https://bitbucket.org/oasychev/moodle-plugins
//
// WriteRegex is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// WriteRegex is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Write Regex question renderer class.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/shortanswer/renderer.php');

/**
 * Generates the output for writeregex questions.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_writeregex_renderer extends qtype_shortanswer_renderer {

    /**
     * Render correct response.
     * @param question_attempt $qa Question attempt.
     * @return string Correct response value.
     */
    public function correct_response (question_attempt $qa) {

        $question = $qa->get_question();

        $answer = $question->get_correct_response();

        if (!$answer) {
            return '';
        }

        return get_string('correctansweris', 'qtype_shortanswer', $answer['answer']);
    }

    /**
     * Get specific feedback.
     * @param question_attempt $qa Question attempt.
     * @return string Specific feedback.
     */
    public function specific_feedback(question_attempt $qa) {

        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');

        if (!$currentanswer) {
            return '';
        }

        // Use hint.
        return $question->get_feedback_for_response(array('answer' => $currentanswer), $qa);
    }

    /**
     * Get feedback value.
     * @param question_attempt $qa Question attempt.
     * @param question_display_options $options Question display options.
     * @return string Feedback value.
     */
    public function feedback(question_attempt $qa, question_display_options $options) {

        $feedback = '';

        $question = $qa->get_question();
        $behaviour = $qa->get_behaviour();
        $currentanswer = $qa->get_last_qt_var('answer');

        if (!$currentanswer) {
            $currentanswer = '';
        }
        else {
            if (!empty($question->bestfitanswer['results'])) {
                $feedback = $question->bestfitanswer['results']['tree']->get_feedback($this) . $question->bestfitanswer['results']['automata']->get_feedback($this) . $question->bestfitanswer['results']['strings']->get_feedback($this);
            }
        }

        $br = html_writer::empty_tag('br');

        if (is_a($behaviour, 'qtype_poasquestion\\behaviour_with_hints')) {
            $hints = $question->available_specific_hints();
            $hints = $behaviour->adjust_hints($hints);

            foreach ($hints as $hintkey) {
                if ($qa->get_last_step()->has_behaviour_var('_render_' . $hintkey)) {
                    $hintobj = $question->hint_object($hintkey);
                    $feedback .= $hintobj->render_hint($this, $qa, $options, array('answer' => $currentanswer)) . $br;
                }
            }
        }

        if (get_class($behaviour) == 'qbehaviour_interactivehints') {
            $hints = $question->available_specific_hints();
            $hints = $behaviour->adjust_hints($hints);
            $hintoptions = explode('\n', $qa->get_applicable_hint()->options);

            foreach ($hints as $index => $hintkey) {
                $hintobj = $question->hint_object($hintkey);
                $hintobj->set_mode($hintoptions[$index]);
                $feedback .= $hintobj->render_hint($this, $qa, $options, array('answer' => $currentanswer)) . $br;
            }
        }

        $output = parent::feedback($qa, $options);

        return $feedback . $output;
    }

    /**
     * Generate two column table for teststring hint with displaying both student and teacher match results
     */
    public function generate_teststring_hint_result_table($studentresults, $teacherresults) {
        // Generating cell for students result.
        $studentresultscell = html_writer::tag('td', $studentresults);
        // Generating cell for teachers result.
        $teacherresultscell = html_writer::tag('td', $teacherresults);
        // Generating row for results.
        $resultsrow = html_writer::tag('tr', $studentresultscell . $teacherresultscell);
        // Generating cell for students result title.
        $studenttitlecell = html_writer::tag('td', get_string('hintdescriptionstudentsanswer', 'qtype_writeregex'));
        // Generating cell for teachers result title.
        $teachertitlecell = html_writer::tag('td', get_string('hintdescriptionteachersanswer', 'qtype_writeregex'));
        // Generating row for result titles.
        $titlerow = html_writer::tag('tr', $studenttitlecell . $teachertitlecell);
        // Generating table.
        $res = html_writer::tag('table', $titlerow . $resultsrow, array('cellpadding' => '5'));
        return $res;
    }

    /**
     * Puts hint title into h-tag.
     */
    public function render_hint_title($hinttitle) {
        return html_writer::tag('h5', $hinttitle);
    }

    /**
     * Adds <br> tag to given string
     */
    public function add_break($string) {
        return $string . html_writer::empty_tag('br');
    }

    /**
     * Adds <span> tag to given string.
     */
    public function add_span($string) {
        return html_writer::tag('span', $string);
    }

    /**
     * Render matched string in automaton.
     * @param string str matched or mismatched string
     * @param bool $matched true, if string matched, false otherwise
     */
    public function render_automaton_matched_string($str, $matched) {
        if ($str == '')
            return $str;
        if ($matched)
            return html_writer::tag('span', htmlspecialchars($str), array('class' => question_state::graded_state_for_fraction(1)->get_feedback_class()));
        return html_writer::tag('span', htmlspecialchars($str), array('class' => question_state::graded_state_for_fraction(0)->get_feedback_class()));
    }

    /**
     * Render matched string in automaton with automaton author.
     * @param string author matched or mismatched string
     * @param string str matched or mismatched string
     * @param bool $matched true, if string matched, false otherwise
     */
    public function render_automaton_matched_string_with_author($author, $str) {
        $result = html_writer::tag('div', $author, array('id' => 'mismatch_description_title'));
        $result .= html_writer::tag('div', $str, array('id' => 'qtype-preg-colored-string'));
        return html_writer::tag('div', $result . html_writer::empty_tag('br'));
    }
}