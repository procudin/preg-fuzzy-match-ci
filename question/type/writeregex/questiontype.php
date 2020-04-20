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
 * Question type class for the write regex question type.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/shortanswer/questiontype.php');

/**
 * Question type class for the write regex question type.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_writeregex extends qtype_shortanswer {

    /** Format value of test string answers. */
    const TEST_STRING_ANSWER_FORMAT_VALUE = 1;

    /**
     * Get question options.
     * @param object $question Question object.
     * @return bool Indicates success or failure.
     */
    public function get_question_options($question) {

        $result = parent::get_question_options($question);

        foreach ($question->options->answers as $key => $answer) {
            if ($answer->answerformat == self::TEST_STRING_ANSWER_FORMAT_VALUE) {
                $question->options->teststrings[$key] = $answer;
                unset($question->options->answers[$key]);
            }
        }

        $question->syntaxtreehint = array();
        $question->explgraphhint = array();
        $question->descriptionhint = array();
        $question->teststringshint = array();

        if (!isset($question->options->teststrings))
            $question->options->teststrings = array();

        return $result;
    }

    /**
     * Save question options.
     * @param object $question Question object.
     * @return object $result->error or $result->noticeyesno or $result->notice
     */
    public function save_question_options($question) {
        global $DB;
        $result = new stdClass();

        // Remove all answers.
        $DB->delete_records('question_answers', array('question' => $question->id));

        if (!isset($question->regexp_ts_answer)) {

            $answers = array();
            $answersstrings = array();
            $fraction = array();
            $fractionstrings = array();

            foreach ($question->answer as $index => $item) {
                if ($question->answerformat[$index] == self::TEST_STRING_ANSWER_FORMAT_VALUE) {
                    $answersstrings[] = $item;
                    $fractionstrings[] = $question->fraction[$index];
                } else {
                    $answers[] = $item;
                    $fraction[] = $question->fraction[$index];
                }
            }

            $question->answer = $answers;
            $question->fraction = $fraction;
            $question->regexp_ts_answer = $answersstrings;
            $question->regexp_ts_fraction = $fractionstrings;
        }

        // Insert regexp answers.
        parent::save_question_options($question);

        // Insert test string answers.
        foreach ($question->regexp_ts_answer as $key => $answer) {

            if (trim($answer) == '' && $question->regexp_ts_fraction[$key] == 0) {
                continue;
            }

            $record = $this->get_test_string_answer_object($answer,
                $question->regexp_ts_fraction[$key], $question->id);

            $DB->insert_record('question_answers', $record);
        }

        return $result;
    }

    /**
     * Save additional question type data into the hint optional field.
     * @param object $formdata the data from the form.
     * @param int $number number of hint to get options from.
     * @param bool $withparts whether question have parts.
     * @return string value to save into the options field of question_hints table.
     */
    protected function save_hint_options($formdata, $number, $withparts) {

        // Add syntaxtreehint.
        $result = $formdata->syntaxtreehint[$number];

        // Add explgraphhint.
        $result .= '\n' . $formdata->explgraphhint[$number];

        // Add descriptionhint.
        $result .= '\n' . $formdata->descriptionhint[$number];

        // Add teststringshint.
        $result .= '\n' . $formdata->teststringshint[$number];

        return $result;
    }

    /**
     * Determine if the hint with specified number is not empty and should be saved.
     * @param object $formdata the data from the form.
     * @param int $number number of hint under question.
     * @param bool $withparts whether to take into account clearwrong and shownumcorrect options.
     * @return bool is this particular hint data empty.
     */
    protected function is_hint_empty_in_form_data($formdata, $number, $withparts) {
        $result = parent::is_hint_empty_in_form_data($formdata, $number, $withparts);

        if ($result and $formdata->syntaxtreehint[$number] == 0 and $formdata->explgraphhint[$number] == 0
            and $formdata->descriptionhint[$number] == 0 and $formdata->teststringshint[$number] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get test string answer object.
     * @param $answer stdClass Test string.
     * @param $fraction float Fraction value.
     * @param $questionid int Questions id.
     * @return stdClass stdClass of answer.
     */
    private function get_test_string_answer_object ($answer, $fraction, $questionid) {

        $result = new stdClass();

        $result->answer = $answer;
        $result->question = $questionid;
        $result->answerformat = self::TEST_STRING_ANSWER_FORMAT_VALUE;
        $result->fraction = $fraction;
        $result->feedback = '';
        $result->feedbackformat = 0;

        return $result;
    }

    /**
     * Get extra question fields.
     * @return array Array of tables columns.
     */
    public function extra_question_fields() {

        return array('qtype_writeregex_options',
            'usecase',                   // Case sensitivity of regex.
            'engine',                    // Regex engine.
            'notation',                  // Regex notation.
            'syntaxtreehinttype',        // Syntax tree hint type.
            'syntaxtreehintpenalty',     // Syntax tree hint penalty.
            'explgraphhinttype',         // Explain graph hint type.
            'explgraphhintpenalty',      // Explain graph hint penalty.
            'descriptionhinttype',       // Description hint type.
            'descriptionhintpenalty',    // Description hint penalty.
            'teststringshinttype',       // Test strings hint type.
            'teststringshintpenalty',    // Test strings hint penalty.
            'comparetreepercentage',     // Percentage value of compare regex by tree (0-100).
            'compareautomatapercentage', // Percentage value of compare regex by automata (0-100).
            'comparestringspercentage'   // Percentage value of compare regex by test strings (0-100).
        );
    }

    /**
     * @return array of available analyzers where key is the keyword and value is description
     */
    public function available_analyzers() {
        return array('tree' => get_string('comparetreepercentage', 'qtype_writeregex'),
            'automata' => get_string('compareautomatapercentage', 'qtype_writeregex'),
            'strings' => get_string('comparestringspercentage', 'qtype_writeregex'));
    }

    /**
     * @return array of available hint types, where key is the keyword and value is class name
     */
    public function available_hint_types() {
        return array('syntaxtree' => 'syntax_tree_hint',
            'explgraph' => 'explanation_graph_hint',
            'description' => 'description_hint',
            'teststrings' => 'test_strings_hint');
    }


    /**
     * Import from xml
     * @param $data array Data from file.
     * @param $question object Question object.
     * @param qformat_xml $format xml Format.
     * @param null $extra Extra options.
     * @return bool|object Question objects.
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {

        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), '');
        }

        // Import hints.
        $hints = $data['#']['hint'];
        $hcount = 0;
        foreach ($hints as $hint) {
            $qo->hint[$hcount]['format'] = 1;
            $qo->hint[$hcount]['text'] = $hint['#']['text']['0']['#'];
            $hintoptions = explode('\n', $hint['#']['options']['0']['#']);
            if (count($hintoptions) == 4) {

                $qo->syntaxtreehint[] = $hintoptions[0];
                $qo->explgraphhint[] = $hintoptions[1];
                $qo->descriptionhint[] = $hintoptions[2];
                $qo->teststringshint[] = $hintoptions[3];
            } else {
                $qo->syntaxtreehint[] = 0;
                $qo->explgraphhint[] = 0;
                $qo->descriptionhint[] = 0;
                $qo->teststringshint[] = 0;
            }
        }

        // Run through the answers.
        $answers = $data['#']['answer'];
        $acount = 0;
        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer, true);
            if (!$this->has_html_answers()) {
                $qo->answer[$acount] = $ans->answer['text'];
            } else {
                $qo->answer[$acount] = $ans->answer;
            }
            $qo->answerformat[$acount] = $ans->answer['format'];
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            if (is_array($extraanswersfields)) {
                foreach ($extraanswersfields as $field) {
                    $qo->{$field}[$acount] =
                        $format->getpath($answer, array('#', $field, 0, '#'), '');
                }
            }
            ++$acount;
        }
        return $qo;
    }

    /** Overload hints functions to be able to work with interactivehints*/
    protected function make_hint($hint) {
        return qtype_poasquestion_moodlehint_adapter::load_from_record($hint);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        $this->initialise_question_teststrings($question, $questiondata);
        parent::initialise_question_instance($question, $questiondata);
    }

    /**
     * Initialise question_definition::teststring field (public for testing).
     * @param question_definition $question the question_definition we are creating.
     * @param object $questiondata the question data loaded from the database.
     * @param bool $forceplaintextanswers most qtypes assume that answers are
     *      FORMAT_PLAIN, and dont use the answerformat DB column (it contains
     *      the default 0 = FORMAT_MOODLE). Therefore, by default this method
     *      ingores answerformat. Pass false here to use answerformat. For example
     *      multichoice does this.
     */
    public function initialise_question_teststrings(question_definition $question,
                                                   $questiondata, $forceplaintextanswers = true) {
        $question->teststrings = array();
        if (empty($questiondata->options->teststrings)) {
            return;
        }
        foreach ($questiondata->options->teststrings as $ind => $teststring) {
            $question->teststrings[$teststring->id] = $this->make_teststring($teststring);
            if (!$forceplaintextanswers) {
                $question->teststrings[$teststring->id]->stringformat = $teststring->answerformat;
            }
            unset($questiondata->options->teststrings[$ind]);
        }
    }

    /**
     * Create a test_string, or an appropriate subclass for this teststring,
     * from a row loaded from the database.
     * @param object $teststring the DB row from the question_answers.
     * @return test_string
     */
    protected function make_teststring($teststring) {
        return new \qtype_writeregex\test_string($teststring->id, $teststring->answer,
            $teststring->fraction, $teststring->feedback, $teststring->feedbackformat);
    }
}
