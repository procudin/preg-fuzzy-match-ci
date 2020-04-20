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
 * Unit tests for question/type/writeregex/question.php.
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

class qtype_writeregex_question_test extends PHPUnit_Framework_TestCase {

    public function test_get_best_fit_answer() {
        $floatcompareepsilon = 0.00001;
        $question = new qtype_writeregex_question();
        $question->engine = 'fa_matcher';
        $question->notation = 'native';
        $question->usecase = 1;
        $question->comparetreepercentage = 0;
        $question->compareautomatapercentage = 0;
        $question->comparestringspercentage = 100;
        $question->teststrings = array(
            new \qtype_writeregex\test_string(0, 'abc', 0.4, null, null),
            new \qtype_writeregex\test_string(0, 'abd', 0.3, null, null),
            new \qtype_writeregex\test_string(0, 'ab', 0.2, null, null),
            new \qtype_writeregex\test_string(0, 'abcd', 0.1, null, null));

        // Single answer.
        $question->hintgradeborder = 0.1;
        $question->answers = array(new question_answer(0, 'ab.', 1, null, null));

        $response = array('answer' => 'ab.');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);

        // Cached answer.
        $question->answers = array();

        $cachedbestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($cachedbestfitanswer == $bestfitanswer);

        // Some answers.
        $question->answers = array(new question_answer(0, 'ab.', 1, null, null),
                                   new question_answer(1, 'abc', 0.6, null, null),
                                   new question_answer(2, 'abcd', 0.4, null, null));

        $response = array('answer' => 'ab[cd]');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);

        $response = array('answer' => 'abc');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[1]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);

        // Response withowt absolute fitness.
        $response = array('answer' => 'abd');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0.5) < $floatcompareepsilon);

        // Response with zero fitness.
        $response = array('answer' => 'qwerty');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0) < $floatcompareepsilon);

        // With high hint grade border.
        $question->hintgradeborder = 0.9;
        $question->answers = array(new question_answer(0, 'abc', 1, null, null),
                                   new question_answer(1, 'ab.', 0.6, null, null),
                                   new question_answer(2, 'abcd', 0.4, null, null));
        $response = array('answer' => 'ab[d]');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0) < $floatcompareepsilon);

        $question->answers = array(new question_answer(0, 'abc', 0.6, null, null),
                                   new question_answer(1, 'ab.', 1, null, null),
                                   new question_answer(2, 'abcd', 0.4, null, null));
        $response = array('answer' => 'a[b][d]');

        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[1]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0) < $floatcompareepsilon);

        // Match tests.
        $question->answers = array(new question_answer(0, 'abq', 1, null, null),
                                   new question_answer(1, 'ab[qd]', 0.9, null, null),
                                   new question_answer(2, 'abcd', 0.5, null, null),
                                   new question_answer(3, 'ae', 0, null, null));
        $question->teststrings = array(
            new \qtype_writeregex\test_string(0, 'abq', 0.25, null, null),
            new \qtype_writeregex\test_string(0, 'abd', 0.25, null, null),
            new \qtype_writeregex\test_string(0, 'ae', 0.25, null, null),
            new \qtype_writeregex\test_string(0, 'abcd', 0.25, null, null));

        // Full match tests.
        // 100% full match.
        $question->hintgradeborder = 0;
        $response = array('answer' => 'abq');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);
        // 100% partial match, 90% full match.
        $response = array('answer' => 'ab[qd]');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[1]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);
        // 100% and 90% partial match, 50% full match.
        $response = array('answer' => 'abcd');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[2]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);
        // 100%, 90%, 50% partial matches, 0% full match.
        $response = array('answer' => 'ae');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[3]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 1) < $floatcompareepsilon);

        //  Partial match testing
        // 100% is closest partial match
        $response = array('answer' => 'aba');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0.75) < $floatcompareepsilon);
        // 90% is closest partial match
        $response = array('answer' => 'abd');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[1]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0.75) < $floatcompareepsilon);
        // 50% is closest partial match
        $response = array('answer' => 'abcc');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[2]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0.75) < $floatcompareepsilon);
        // 0% is closest partial match
        $response = array('answer' => 'aa');
        $bestfitanswer = $question->get_best_fit_answer($response);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[3]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0.75) < $floatcompareepsilon);
        // 50% is better, but it isn't within hint grade border, while all answers within border have no matches.
        // So 100% is choosen as first answer within border with no match at all.
        $question->hintgradeborder = 0.8;
        $response = array('answer' => 'abcc');
        $bestfitanswer = $question->get_best_fit_answer($response);
        var_dump($bestfitanswer);
        $this->assertTrue($bestfitanswer['answer'] == $question->answers[0]);
        $this->assertTrue(abs($bestfitanswer['fitness'] - 0) < $floatcompareepsilon);
    }
}