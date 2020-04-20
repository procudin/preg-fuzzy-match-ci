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

namespace qtype_writeregex;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_regex_testing_tool.php');
require_once($CFG->dirroot . '/question/type/preg/preg_matcher.php');

/**
 * Class for writeregex test strings hints.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_strings_hint extends hint {

    /**
     * Get hint title.
     * @return string hint title.
     */
    public function hint_title() {
        return get_string('teststringshinttype', 'qtype_writeregex');
    }

    /**
     * @return string key for lang strings and field names
     */
    public function short_key() {
        return 'teststrings';
    }

    /**
     * Returns the hint explanation string to show after hint usage.
     * @return string hint explanation
     */
    public function hint_explanation_title() {
        return get_string('teststringshintexplanation', 'qtype_writeregex',
            get_string('hinttitleadditionformode_' . $this->mode, 'qtype_writeregex'));
    }

    /**
     * @return qtype_preg_authoring_tool tool used for hint
     */
    public function tool($regex) {
        $strings = '';
        $key = 0;
        foreach ($this->question->teststrings as $item) {

            if ($key == count($this->question->teststrings) - 1) {
                $strings .= $item->teststring;
            } else {
                $strings .= $item->teststring . "\n";
                $key++;
            }
        }
        $usecase = $this->question->usecase;
        $exactmatch = false;
        $engine = $this->question->engine;
        $notation = $this->question->notation;
        return new \qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine,
            $notation, new \qtype_preg_position());
    }

    /**
     * Render hint for concrete regex.
     * @param string $regex regex for which hint is to be shown
     * @return string hint display result for given regex.
     */
    public function render_hint_for_answer($answer) {
        $tree = $this->tool($answer);
        $json = $tree->generate_json();
        return $json['regex_test'];
    }

    /**
     * Render hint for both students and teachers answers.
     * @param string $studentsanswer students answer
     * @param string $teachersanswer teachers answer
     * @param question $renderer
     * @return string hint display result for given answers.
     */
    public function render_hint_for_both_students_and_teachers_answers($studentsanswer, $teachersanswer, $renderer) {
        $hintforstudent = $this->render_hint_for_answer($studentsanswer);
        $hintforteacher = $this->render_hint_for_answer($teachersanswer);
        return $renderer->generate_teststring_hint_result_table($hintforstudent, $hintforteacher);
    }
}
