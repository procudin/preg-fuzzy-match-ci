<?php
// This file is part of WriteRegex question type - https://bitbucket.org/oasychev/moodle-pluginss
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

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/question.php');

/**
 * Class analyser fot test strings.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compare_strings_analyzer extends analyzer {

    /**
     * Get equality for user response.
     * @param $answer string Regex answer.
     * @param $response string User response.
     * @return compare_strings_analyzer_result Result of compare.
     */
    public function analyze($answer, $response) {

        $totalfraction = 0;

        $pregquestionstd = new \qtype_preg_question();
        $matchingoptions = $pregquestionstd->get_matching_options(false, $pregquestionstd->get_modifiers($this->question->usecase), null, $this->question->notation);
        $matchingoptions->extensionneeded = false;
        $matchingoptions->capturesubexpressions = true;

        $studentmatcher = $pregquestionstd->get_matcher($this->question->engine, $answer, $matchingoptions);

        $teachermatcher = $pregquestionstd->get_matcher($this->question->engine, $response, $matchingoptions);

        foreach ($this->question->teststrings as $string) {

            if (!$studentmatcher->errors_exist() and !$teachermatcher->errors_exist()) {
                $resulltstd = $studentmatcher->match($string->teststring);
                $resulltt = $teachermatcher->match($string->teststring);

                if ($resulltstd->indexfirst == $resulltt->indexfirst and
                    $resulltstd->length == $resulltt->length) {
                    $totalfraction += $string->fraction;
                }
            }
        }

        // Generate result
        $result = new compare_strings_analyzer_result();
        $result->fitness = $totalfraction;
        return $result;
    }

    /**
     * Get analyzer name
     * @return analyzer name, understandable for user
     */
    public function name()
    {
        return get_string('comparestringsanalyzername', 'qtype_writeregex');
    }
}