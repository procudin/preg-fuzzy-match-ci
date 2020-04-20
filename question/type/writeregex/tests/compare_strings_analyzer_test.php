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
 * Unit tests for question/type/writeregex/classes/compare_strings_analyzer.php.
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

class qtype_writeregex_compare_strings_analyzer_test extends PHPUnit_Framework_TestCase {

    public function test_get_fitness() {
        $question = new qtype_writeregex_question();
        $question->engine = 'fa_matcher';
        $question->notation = 'native';
        $question->usecase = 1;
        $question->teststrings = array(new \qtype_writeregex\test_string(1, 'abc', 0.2, '', ''),
                                       new \qtype_writeregex\test_string(2, 'abcd', 0.3, '', ''),
                                       new \qtype_writeregex\test_string(3, 'abce', 0.4, '', ''),
                                       new \qtype_writeregex\test_string(4, 'abq', 0.1, '', ''));

        $comparestringsanalyzer = new \qtype_writeregex\compare_strings_analyzer($question);
        $epsilon = 0.00001;

        // Absolute match
        $this->assertTrue(abs($comparestringsanalyzer->get_fitness('abc[de]', 'abc[de]') - 1.0) < $epsilon);
        // Match to all but one teststrings
        $this->assertTrue(abs($comparestringsanalyzer->get_fitness('abc[de]', 'abcd') - 0.6) < $epsilon);
        // Match to some teststrings
        $this->assertTrue(abs($comparestringsanalyzer->get_fitness('abc[de]', 'abc') - 0.3) < $epsilon);
        // Match to only one teststring
        $this->assertTrue(abs($comparestringsanalyzer->get_fitness('abc[de]', 'ab[cq]') - 0.2) < $epsilon);
        // Absolute mismatch
        $this->assertTrue(abs($comparestringsanalyzer->get_fitness('abc[de]', 'qwe[rt]')) < $epsilon);
    }
}
