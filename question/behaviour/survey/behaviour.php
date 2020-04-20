<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
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
 * Behaviour where question is used as survey.
 *
 * @package    qbehaviour
 * @subpackage survey
 * @copyright  2017, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Behaviour where question is used as survey.
 *
 * @copyright  2017, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/behaviour/immediatefeedback/behaviour.php');

class qbehaviour_survey extends qbehaviour_immediatefeedback {

    public function get_state_string($showcorrectness) {
        $state = $this->qa->get_state();
        if ($state == question_state::$todo) {
            return get_string('notcomplete', 'qbehaviour_survey');
        } else {
            return parent::get_state_string($showcorrectness);
        }
    }

    public function process_submit(question_attempt_pending_step $pendingstep) {
        if (!$this->is_complete_response($pendingstep)) {
            $pendingstep->set_state(question_state::$gaveup);
        } else {
            $response = $pendingstep->get_qt_data();
            list($fraction, $state) = $this->question->grade_response($response);
            $pendingstep->set_fraction(1.0);
            $pendingstep->set_state(question_state::$gradedright);
            $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        }
        return question_attempt::KEEP;
    }

    public function process_finish(question_attempt_pending_step $pendingstep) {
        $response = $this->qa->get_last_step()->get_qt_data();
        if (!$this->question->is_gradable_response($response)) {
            $pendingstep->set_state(question_state::$gaveup);
        } else {
            list($fraction, $state) = $this->question->grade_response($response);
            $pendingstep->set_fraction(1.0);
            $pendingstep->set_state(question_state::$gradedright);
        }
        $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        return question_attempt::KEEP;
    }
}
