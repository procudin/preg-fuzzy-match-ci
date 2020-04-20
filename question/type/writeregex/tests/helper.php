<?php
// This file is part of Preg question type - https://bitbucket.org/oasychev/moodle-plugins
//
// Preg question type is free software: you can redistribute it and/or modify
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
 * Test helper code for the Writeregex question type.
 *
 * @package    qtype
 * @subpackage writeregex
 * @copyright  2015 Oleg Sychev, Volgograd State Technical University
 * @author     Kamo Spertsian <spertsiankamo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the multiple choice question type.
 */
class qtype_writeregex_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('one_regex_two_teststings');
    }

    /**
     * Get the question data, as it would be loaded by get_question_options.
     * @return object
     */
    public static function get_writeregex_question_data_one_regex_two_teststings() {
        global $USER;

        $qdata = new stdClass();

        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->qtype = 'writeregex';
        $qdata->name = 'Write regex question';
        $qdata->questiontext = 'Question text';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = '';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;

        $qdata->options = new stdClass();
        $qdata->options->usecase = false;
        $qdata->options->correctanswer = '';
        $qdata->options->exactmatch = true;
        $qdata->options->notation = 'native';
        $qdata->options->engine = 'fa_matcher';
        $qdata->options->usecharhint = true;
        $qdata->options->uselexemhint = true;

        $qdata->options->answers = array(
            13 => (object) array(
                'id' => 13,
                'answer' => '000',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => '0.5',
                'feedback' => 'No tests.',
                'feedbackformat' => FORMAT_HTML,
                'regextests' => null
            )
        );

        $qdata->hints = array(
            1 => (object) array(
                'hint' => 'Hint 1.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 0,
                'clearwrong' => 0,
                'options' => 'hintmatchingpart',
            ),
            2 => (object) array(
                'hint' => 'Hint 2.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 1,
                'clearwrong' => 1,
                'options' => 'hintnextchar',
            ),
        );

        return $qdata;
    }

    /**
     * Get the question data, as it would be loaded by get_question_options.
     * @return object
     */
    public static function get_writeregex_question_form_data_one_regex_two_teststings() {
        $qdata = new stdClass();

        $qdata->name = 'Write regex question';
        $qdata->questiontext = array('text' => 'Question text', 'format' => FORMAT_HTML);
        $qdata->generalfeedback = array('text' => '', 'format' => FORMAT_HTML);
        $qdata->defaultmark = 1;
        $qdata->noanswers = 1;
        $qdata->penalty = 0.3333333;

        $qdata->usecase = 1;
        $qdata->correctanswer = '';
        $qdata->exactmatch = 1;
        $qdata->notation = 'native';
        $qdata->engine = 'fa_matcher';
        $qdata->usecharhint = true;
        $qdata->uselexemhint = true;

        $qdata->fraction = array(0 => '0.5');
        $qdata->answer = array(
            0 => 'abc'
        );

        $qdata->feedback = array(
            0 => array(
                'text' => '',
                'format' => FORMAT_PLAIN
            )
        );

        return $qdata;
    }
}