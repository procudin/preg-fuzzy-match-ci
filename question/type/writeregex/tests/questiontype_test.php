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
 * Unit tests for question/type/writeregex/questiontype.php.
 *
 * @package    qtype
 * @subpackage writeregex
 * @copyright  2015 Oleg Sychev, Volgograd State Technical University
 * @author     Kamo Spertsian <spertsiankamo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/question.php');
require_once($CFG->dirroot . '/question/type/writeregex/question.php');
require_once($CFG->dirroot . '/question/type/writeregex/questiontype.php');

class qtype_writeregex_questiontype_test extends PHPUnit_Framework_TestCase {

    public function make_std_teststring($id, $answer, $answerformat, $fraction) {
        $teststring = new stdClass();
        $teststring->id = $id;
        $teststring->question = 1;
        $teststring->answer = $answer;
        $teststring->answerformat = $answerformat;
        $teststring->fraction = $fraction;
        $teststring->feedback = '';
        $teststring->feedbackformat = '';

        return $teststring;
    }

    public function make_teststring($teststring) {
        return new \qtype_writeregex\test_string($teststring->id, $teststring->answer,
            $teststring->fraction, $teststring->feedback, $teststring->feedbackformat);
    }

    public function test_initialise_question_teststrings() {
        $question = new qtype_writeregex_question();
        $questiontype = new qtype_writeregex();

        $questiondata = new stdClass();
        $questiondata->options = new stdClass();
        $questiondata->options->teststrings = array();

        // Test empty teststrings.
        $questiontype->initialise_question_teststrings($question, $questiondata);
        $this->assertTrue(empty($question->teststrings));

        // Test single teststring.
        $teststring = $this->make_std_teststring(1, 'abc', $questiontype::TEST_STRING_ANSWER_FORMAT_VALUE, 0.5);
        $questiondata->options->teststrings[] = $teststring;
        $expteststrings = array($teststring->id => $this->make_teststring($teststring));
        $questiontype->initialise_question_teststrings($question, $questiondata);
        $this->assertTrue($question->teststrings == $expteststrings && empty($questiondata->options->teststrings));

        // Test some teststrings.
        $questiondata->options->teststrings = array();
        $questiondata->options->teststrings[] = $this->make_std_teststring(1, 'abc', $questiontype::TEST_STRING_ANSWER_FORMAT_VALUE, 0.5);
        $questiondata->options->teststrings[] = $this->make_std_teststring(2, 'abd', $questiontype::TEST_STRING_ANSWER_FORMAT_VALUE, 0.7);
        $questiondata->options->teststrings[] = $this->make_std_teststring(3, 'abe', $questiontype::TEST_STRING_ANSWER_FORMAT_VALUE, 0.9);
        $expteststrings = array($questiondata->options->teststrings[0]->id => $this->make_teststring($questiondata->options->teststrings[0]),
                                $questiondata->options->teststrings[1]->id => $this->make_teststring($questiondata->options->teststrings[1]),
                                $questiondata->options->teststrings[2]->id => $this->make_teststring($questiondata->options->teststrings[2]));
        $questiontype->initialise_question_teststrings($question, $questiondata);
        $this->assertTrue($question->teststrings == $expteststrings && empty($questiondata->options->teststrings));
    }
}
