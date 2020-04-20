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

/**
 * Class to represent a writeregex question teststring, loaded from the question_answers table
 * in the database.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Spertsian Kamo <spertsiankamo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_string {
    /** @var integer the teststring id. */
    public $id;

    /** @var string the teststring. */
    public $teststring;

    /** @var integer one of the FORMAT_... constans. */
    public $stringformat = FORMAT_PLAIN;

    /** @var number the fraction this teststring is worth. */
    public $fraction;

    /** @var string the feedback for this teststring. */
    public $feedback;

    /** @var integer one of the FORMAT_... constans. */
    public $feedbackformat;

    /**
     * Constructor.
     * @param int $id the teststring.
     * @param string $teststring the teststring.
     * @param number $fraction the fraction this teststring is worth.
     * @param string $feedback the feedback for this teststring.
     * @param int $feedbackformat the format of the feedback.
     */
    public function __construct($id, $teststring, $fraction, $feedback, $feedbackformat) {
        $this->id = $id;
        $this->teststring = $teststring;
        $this->fraction = $fraction;
        $this->feedback = $feedback;
        $this->feedbackformat = $feedbackformat;
    }
}