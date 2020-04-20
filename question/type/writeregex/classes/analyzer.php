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

/**
 * Base analyser class.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Kamo Spertsian <spertsiankamo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class analyzer {

    /** @var  object Question object. */
    protected $question;

    /**
     * Init analyzer object.
     * @param $question object Question object.
     */
    public function __construct($question) {
        $this->question = $question;
    }

    /**
     * Get equality for user response.
     * @param $answer string Regex answer.
     * @param $response string User response.
     * @return analyzer_result Result of compare.
     */
    public abstract function analyze($answer, $response);

    /**
     * Get analyzer name
     * @return analyzer name, understandable for user
     */
    public abstract function name();
}