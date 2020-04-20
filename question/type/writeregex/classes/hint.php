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

/**
 * Base class for writeregex specific hints.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class hint extends \qtype_poasquestion\hint {

    /** Mode, when hint is disabled */
    const HINT_DISABLED = 0;
    /** Mode, when hint is enabled for students answer */
    const HINT_FOR_STUDENTS_ANSWER = 1;
    /** Mode, when hint is enabled for teachers answer */
    const HINT_FOR_TEACHERS_ANSWER = 2;
    /** Mode, when hint is enabled for both students and teachers answers */
    const HINT_FOR_BOTH_STUDENTS_AND_TEACHERS_ANSWERS = 3;

    /** @var  int Mode of hint. */
    protected $mode;
    /** @var  object Object on question. */
    protected $question;
    /** @var  string Hint key. */
    protected $hintkey;

    /**
     * Init all fields.
     * @param $question object Object of question's class.
     * @param $hintkey string Hint key.
     * @param $mode int Mode of current hint.
     */
    public function __construct ($question, $hintkey, $mode) {

        $this->question = $question;
        $this->hintkey = $hintkey;
        $this->mode = $mode;
    }

    /**
     * Sets mode of hint
     * @param $value new mode
     */
    public function  set_mode ($value) {
        $this->mode = $value;
    }

    /**
     * Get hint type.
     * @return int hint type.
     */
    public function hint_type() {
        return \qtype_poasquestion\hint::SINGLE_INSTANCE_HINT;
    }

    /**
     * @return string hint title
     */
    public abstract function hint_title();

    /**
     * Get hint description.
     * @return string hint description.
     */
    public function hint_description() {
        // Description for hint using mode (e.g. '(for correct answer)').
        $additionformode = get_string('hinttitleaddition', 'qtype_writeregex',
            get_string('hinttitleadditionformode_' . $this->mode, 'qtype_writeregex'));
        return \qtype_poasquestion\utf8_string::strtolower($this->hint_title()) . ' ' . $additionformode;
    }

    /**
     * Get value of hint response based or not.
     * @return bool hint response based.
     */
    public function hint_response_based() {
        return $this->mode == $this::HINT_FOR_STUDENTS_ANSWER || $this->mode == $this::HINT_FOR_BOTH_STUDENTS_AND_TEACHERS_ANSWERS;
    }

    /**
     * @return string key for lang strings and field names
     */
    public abstract function short_key();

    /**
     * Get penalty value.
     * @param null $response object Response.
     * @return float Value of current hint penalty.
     */
    public function penalty_for_specific_hint ($response = null) {
        $fieldname = $this->short_key() . 'hintpenalty';
        return $this->question->$fieldname;
    }

    /**
     * @return \qtype_preg_authoring_tools_options regexoptions for current question
     */
    public function get_regex_options() {
        $regexoptions = new \qtype_preg_authoring_tools_options();
        $regexoptions->engine = $this->question->engine;
        $regexoptions->usecase = $this->question->usecase;
        $regexoptions->notation = $this->question->notation;
        return $regexoptions;
    }

    /**
     * @return qtype_preg_authoring_tool tool used for hint
     */
    public abstract function tool($regex);

    /**
     * Get hint available.
     * @param null $response Response.
     * @return bool Hint available.
     */
    public function hint_available ($response = null) {

        $hinttypefield = $this->short_key() . 'hinttype';
        switch ($this->question->$hinttypefield) {
            case $this::HINT_DISABLED:
                return false;
            case $this::HINT_FOR_TEACHERS_ANSWER:
                return true;
            default:
                if ($response == null) {
                    return true;
                }

                // Check for possibility of using hint for current student response.
                $tree = $this->tool($response['answer']);
                if ($tree->errors_exist()) {
                    return false;
                }
                return true;
        }
        return true;
    }

    /**
     * Render hint for concrete regex.
     * @param string $regex regex for which hint is to be shown
     * @return string hint display result for given regex.
     */
    public function render_hint_for_answer($answer) {
        $tool = $this->tool($answer);
        $json = $tool->generate_json();
        return $json[$tool->json_key()]['img'];
    }

    /**
     * Render hint for both students and teachers answers.
     * @param string $studentsanswer students answer
     * @param string $teachersanswer teachers answer
     * @return string hint display result for given answers.
     */
    public function render_hint_for_both_students_and_teachers_answers($studentsanswer, $teachersanswer, $renderer) {
        $hintforstudent = $this->render_hint_for_answer($studentsanswer);
        $hintforteacher = $this->render_hint_for_answer($teachersanswer);
        return get_string('hintdescriptionstudentsanswer', 'qtype_writeregex') . ':<br>' . $hintforstudent . "<br>" .
        get_string('hintdescriptionteachersanswer', 'qtype_writeregex') . ':<br>' . $hintforteacher;
    }

    /**
     * Returns the hint explanation string to show after hint usage.
     * @return string hint explanation
     */
    public function hint_explanation_title() {
        $a = new \stdClass;
        $a->type = get_string($this->short_key() . 'hinttype', 'qtype_writeregex');
        $a->mode = get_string('hinttitleadditionformode_' . $this->mode, 'qtype_writeregex');
        return get_string('hintexplanation', 'qtype_writeregex', $a);
    }

    /**
     * Render hint function.
     * @param question $renderer
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param null $response
     * @return string Template code value.
     */
    public function render_hint($renderer, \question_attempt $qa = null,
                                \question_display_options $options = null, $response = null) {

        $hinttitlestring = $renderer->render_hint_title($this->hint_explanation_title());

        switch($this->mode){
            case $this::HINT_FOR_STUDENTS_ANSWER:
                return $hinttitlestring . $this->render_hint_for_answer($response['answer']);
            case $this::HINT_FOR_TEACHERS_ANSWER:
                $answer = $this->question->get_best_fit_answer($response);
                return $hinttitlestring . $this->render_hint_for_answer($answer['answer']->answer);
            case $this::HINT_FOR_BOTH_STUDENTS_AND_TEACHERS_ANSWERS:
                $answer = $this->question->get_best_fit_answer($response);
                return $hinttitlestring . $this->render_hint_for_both_students_and_teachers_answers($response['answer'],
                    $answer['answer']->answer, $renderer);
            default:
                return '';
        }
    }
}