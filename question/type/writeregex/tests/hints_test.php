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
 * Unit tests for qtype_writeregex hints.
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

class qtype_writeregex_hints_test extends PHPUnit_Framework_TestCase {

    public function test_hint_available() {
        $question = new qtype_writeregex_question();
        $question->engine = 'fa_matcher';
        $question->notation = 'native';
        $question->usecase = 1;
        $question->teststrings = array(new \qtype_writeregex\test_string(1, 'abc', 0.5, '', ''),
                                       new \qtype_writeregex\test_string(2, 'abd', 0.7, '', ''),
                                       new \qtype_writeregex\test_string(3, 'abe', 0.9, '', ''));

        $hints = array('descriptionhinttype' => 'description_hint',
                       'explgraphhinttype' => 'explanation_graph_hint',
                       'syntaxtreehinttype' => 'syntax_tree_hint',
                       'teststringshinttype' => 'test_strings_hint');

        foreach ($hints as $hinttype => $hintclass) {
            $fullclassname = '\\qtype_writeregex\\' . $hintclass;
            // For disabled hint.
            $hint = new $fullclassname($question, $hintclass, \qtype_writeregex\hint::HINT_DISABLED);
            $question->$hinttype = \qtype_writeregex\hint::HINT_DISABLED;
            $this->assertFalse($hint->hint_available());
            // For teacher answer based hint.
            $hint = new $fullclassname($question, $hintclass, \qtype_writeregex\hint::HINT_FOR_TEACHERS_ANSWER);
            $question->$hinttype = \qtype_writeregex\hint::HINT_FOR_TEACHERS_ANSWER;
            $this->assertTrue($hint->hint_available());
            // For response based hint.
            $hint = new $fullclassname($question, $hintclass, \qtype_writeregex\hint::HINT_FOR_TEACHERS_ANSWER);
            $question->$hinttype = \qtype_writeregex\hint::HINT_FOR_STUDENTS_ANSWER;
            // With null response.
            $this->assertTrue($hint->hint_available());
            // With correct response.
            $this->assertTrue($hint->hint_available(array('answer' => 'abc')));
            // With incorrect response
            if ($hintclass == 'explanation_graph_hint' || $hintclass == 'test_strings_hint')
                $this->assertFalse($hint->hint_available(array('answer' => 'ab[c')));
            else
                $this->assertTrue($hint->hint_available(array('answer' => 'ab[c')));
        }
    }
}
