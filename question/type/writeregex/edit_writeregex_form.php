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
 * Edited form for question type Write Regex.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/shortanswer/edit_shortanswer_form.php');
require_once($CFG->dirroot . '/question/type/preg/questiontype.php');
require_once($CFG->dirroot . '/question/type/writeregex/questiontype.php');
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_syntax_tree_tool.php');
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_explaining_graph_tool.php');

/**
 * Edited form for question type Write Regex.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_writeregex_edit_form extends qtype_shortanswer_edit_form {

    /** @var array Array of hints types. */
    private $hintsoptions = array();

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {

        global $CFG;

        $menu = array(
            get_string('caseno', 'qtype_shortanswer'),
            get_string('caseyes', 'qtype_shortanswer')
        );
        $mform->addElement('select', 'usecase',
            get_string('casesensitive', 'qtype_shortanswer'), $menu);

        // Init hints options.
        $this->hintsoptions = array(
            \qtype_writeregex\hint::HINT_DISABLED => get_string('none', 'qtype_writeregex'),
            \qtype_writeregex\hint::HINT_FOR_STUDENTS_ANSWER => get_string('student', 'qtype_writeregex'),
            \qtype_writeregex\hint::HINT_FOR_TEACHERS_ANSWER => get_string('answer', 'qtype_writeregex'),
            \qtype_writeregex\hint::HINT_FOR_BOTH_STUDENTS_AND_TEACHERS_ANSWERS => get_string('both', 'qtype_writeregex')
        );

        // Include preg.
        $pregclass = 'qtype_preg';
        $pregquestionobj = new $pregclass;

        // Add engines.
        $engines = $pregquestionobj->available_engines();
        $mform->addElement('select', 'engine', get_string('engine', 'qtype_preg'), $engines);
        $mform->setDefault('engine', $CFG->qtype_preg_defaultengine);
        $mform->addHelpButton('engine', 'engine', 'qtype_preg');

        // Add notations.
        $notations = $pregquestionobj->available_notations();
        $mform->addElement('select', 'notation', get_string('notation', 'qtype_preg'), $notations);
        $mform->setDefault('notation', $CFG->qtype_preg_defaultnotation);
        $mform->addHelpButton('notation', 'notation', 'qtype_preg');

        // Add all analizers percentages.
        $questiontype = new qtype_writeregex();
        $analyzers = $questiontype->available_analyzers();
        foreach ($analyzers as $name => $description) {
            $constantstringname = 'compare' . $name . 'percentage';
            $mform->addElement('text', $constantstringname,
                get_string($constantstringname, 'qtype_writeregex'));
            $mform->setType($constantstringname, PARAM_FLOAT);
            $mform->setDefault($constantstringname, '0');
            $mform->addHelpButton($constantstringname, $constantstringname, 'qtype_writeregex');
        }
        // Set as default 100% to teststrings analizer.
        $mform->setDefault($constantstringname, '100');

        // Add hints.
        $mform->addElement('header', 'hintshdr', get_string('hintsheader', 'qtype_writeregex'), '');
        $mform->setExpanded('hintshdr', 1);
        // Add syntax tree options.
        $mform->addElement('select', 'syntaxtreehinttype', get_string('syntaxtreehinttype', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('syntaxtreehinttype', 'syntaxtreehinttype', 'qtype_writeregex');
        $mform->addElement('text', 'syntaxtreehintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('syntaxtreehintpenalty', PARAM_FLOAT);
        $mform->setDefault('syntaxtreehintpenalty', '0.0000000');
        $mform->addHelpButton('syntaxtreehintpenalty', 'syntaxtreehintpenalty', 'qtype_writeregex');

        // Add explaining graph options.
        $mform->addElement('select', 'explgraphhinttype', get_string('explgraphhinttype', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('explgraphhinttype', 'explgraphhinttype', 'qtype_writeregex');
        $mform->addElement('text', 'explgraphhintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->addHelpButton('explgraphhintpenalty', 'explgraphhintpenalty', 'qtype_writeregex');
        $mform->setType('explgraphhintpenalty', PARAM_FLOAT);
        $mform->setDefault('explgraphhintpenalty', '0.0000000');

        // Add description options.
        $mform->addElement('select', 'descriptionhinttype', get_string('descriptionhinttype', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('descriptionhinttype', 'descriptionhinttype', 'qtype_writeregex');
        $mform->addElement('text', 'descriptionhintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('descriptionhintpenalty', PARAM_FLOAT);
        $mform->setDefault('descriptionhintpenalty', '0.0000000');
        $mform->addHelpButton('descriptionhintpenalty', 'descriptionhintpenalty', 'qtype_writeregex');

        // Add test string option.
        $mform->addElement('select', 'teststringshinttype', get_string('teststringshinttype', 'qtype_writeregex'),
            $this->hintsoptions);
        $mform->addHelpButton('teststringshinttype', 'teststringshinttype', 'qtype_writeregex');
        $mform->addElement('text', 'teststringshintpenalty',
            get_string('penalty', 'qtype_writeregex'));
        $mform->setType('teststringshintpenalty', PARAM_FLOAT);
        $mform->setDefault('teststringshintpenalty', '0.0000000');
        $mform->addHelpButton('teststringshintpenalty', 'teststringshintpenalty', 'qtype_writeregex');

        // Add answers fields.
        $this->add_per_answer_fields($mform, 'regexp_answers', question_bank::fraction_options(), 1);

        $this->add_per_test_string_fields($mform, 'regexp_ts', question_bank::fraction_options(), 5);

        $this->add_interactive_settings();
    }

    /**
     * Insert fields for regexp answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param $repeatedoptions array Repeated options.
     * @param $answersoption array Answers option
     * @return array Group of fields.
     */
    protected function  get_per_answer_fields($mform, $label, $gradeoptions,
                                                   &$repeatedoptions, &$answersoption) {

        $repeated = array();
        $answeroptions = array();
        $answeroptions[] = $mform->CreateElement('hidden', 'freply', 'yes');
        $repeated[] = $mform->createElement('group', 'answeroptions',
            '', $answeroptions, null, false);
        $repeated[] = $mform->createElement('textarea', 'answer',
            get_string($label, 'qtype_writeregex'), 'wrap="virtual" rows="4" cols="80"');
        $repeated[] = $mform->createElement('select', 'fraction',
            get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback',
            get_string('feedback', 'question'), array('rows' => 8, 'cols' => 80), $this->editoroptions);
        $repeatedoptions['freply']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;

    }

    /**
     * Add fields for test strings answers.
     * @param $mform MoodleForm Form variable.
     * @param $label string Label of group fields.
     * @param $gradeoptions array Grade options array.
     * @param int $minoptions Min options value.
     * @param int $addoptions Additional options value
     */
    private function add_per_test_string_fields(&$mform, $label, $gradeoptions,
                                      $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $mform->addElement('header', 'teststrhdr', get_string($label."_header", 'qtype_writeregex'), '');
        $mform->setExpanded('teststrhdr', 1);

        $answersoption = '';
        $repeatedoptions = array();
        $repeated = array();

        $repeated[] =& $mform->createElement('textarea', $label . '_answer',
            get_string($label, 'qtype_writeregex'), 'wrap="virtual" rows="2" cols="80"', $this->editoroptions);

        $repeated[] =& $mform->createElement('select', $label . '_fraction', get_string('grade'), $gradeoptions);

        $repeatedoptions[$label . '_answer']['type'] = PARAM_RAW;
        $repeatedoptions['test_string_id']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = $label;

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->teststrings);
        } else {
            $repeatsatstart = $minoptions;
        }

        if ($repeatsatstart == 0)
            $repeatsatstart = $minoptions;

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
            'noteststrings', 'addteststrings', $addoptions,
            $this->get_more_choices_string(), true);
    }

    /**
     * Get value string for label which showing text for more choices string.
     * @return string String from lang file.
     */
    protected function get_more_choices_string() {
        return get_string('addmorechoiceblanks', 'question');
    }

    /**
     * Create the form elements required by one hint.
     * @param bool $withclearwrong whether this quesiton type uses the 'Clear wrong' option on hints.
     * @param bool $withshownumpartscorrect whether this quesiton type uses the 'Show num parts correct' option on hints.
     * @return array form field elements for one hint.
     */
    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $repeated = array();

        $parentresult = parent::get_hint_fields($withclearwrong, $withshownumpartscorrect);

        // Add our inputs.
        $mform = $this->_form;
        $count = count($parentresult[0]);

        // Add syntax tree options.
        $repeated[$count++] = $mform->createElement('select', 'syntaxtreehint',
            get_string('syntaxtreehinttype', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add explaining graph options.
        $repeated[$count++] = $mform->createElement('select', 'explgraphhint',
            get_string('explgraphhinttype', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add description options.
        $repeated[$count++] = $mform->createElement('select', 'descriptionhint',
            get_string('descriptionhinttype', 'qtype_writeregex'),
            $this->hintsoptions);

        // Add test string option.
        $repeated[$count] = $mform->createElement('select', 'teststringshint',
            get_string('teststringshinttype', 'qtype_writeregex'),
            $this->hintsoptions);

        $parentresult[0] = array_merge($parentresult[0], $repeated);

        return $parentresult;
    }

    /**
     * Prepare answers data.
     * @param object $question Question's object.
     * @param bool $withanswerfiles If question has answers files.
     * @return object Question's object.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {

        if (empty($question->options->answers)) {
            return $question;
        }

        // Separate answers to regexes and test strings.
        $key = 0;
        $index = 0;
        foreach ($question->options->answers as $answer) {

            $question->answer[$key] = $answer->answer;
            unset($this->_form->_defaultValues["fraction[$key]"]);
            $question->fraction[$key] = $answer->fraction;
            $question->feedback[$key] = array();
            $question->feedback[$key]['text'] = $answer->feedback;
            $question->feedback[$key]['format'] = $answer->feedbackformat;
            $key++;
        }
        foreach ($question->options->teststrings as $teststring) {

            $question->regexp_ts_answer[$index] = $teststring->answer;
            $question->regexp_ts_fraction[$index] = $teststring->fraction;
            $index++;
        }

        return $question;

    }

    /**
     * Validate form fields
     * @param array $data Forms data.
     * @param array $files Files.
     * @return array Errors array.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate availability of using automata analyzer.
        if ($data['engine'] == 'php_preg_matcher' && $data['compareautomatapercentage'] > 0) {
            $errors['engine'] = get_string('unavailableautomataanalyzer', 'qtype_writeregex');
        }

        $errors = $this->validate_compare_values($data, $errors);

        $errors = $this->validate_test_strings($data, $errors);

        $errors = $this->validate_regexp($data, $errors);

        $errors = $this->validate_dot_using_hints($data, $errors);

        return $errors;
    }

    /**
     * Validate regexp answers (public for testing).
     * @param $data array Forms data.
     * @param $errors array Errors array.
     * @return array Errors array.
     */
    public function validate_regexp ($data, $errors) {

        global $CFG;
        $test = $data['comparestringspercentage'];

        if ($test > 0 && $test <= 100) {

            $answers = $data['answer'];
            $i = 0;

            foreach ($answers as $key => $answer) {
                $trimmedanswer = trim($answer);
                if ($trimmedanswer !== '') {
					$pregquestionobj = new qtype_preg_question();
                    $matchingoptions = $pregquestionobj->get_matching_options(false, $pregquestionobj->get_modifiers($data['usecase']), null, $data['notation']);
                    $matchingoptions->extensionneeded = false;
                    $matchingoptions->capturesubexpressions = true;
                    $matcher = $pregquestionobj->get_matcher($data['engine'], $trimmedanswer, $matchingoptions);

                    if ($matcher->errors_exist()) {
                        $regexerrors = $matcher->get_error_messages($CFG->qtype_writregex_maxerrorsshown);
                        $errors['answer['.$key.']'] = '';
                        foreach ($regexerrors as $item) {
                            $errors['answer['.$key.']'] .= $item . '<br />';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validation test string answer (public for testing).
     * @param $data array Data from form.
     * @param $errors array Errors array.
     * @return array Errors array.
     */
    public function validate_test_strings ($data, $errors) {
        $strings = $data['regexp_ts_answer'];
        $answercount = 0;
        $sumgrade = 0;

        foreach ($strings as $key => $item) {
            $trimdata = trim($item);
            if ($trimdata !== '') {
                $answercount++;
                $sumgrade = $sumgrade + $data['regexp_ts_fraction'][$key];
            }
        }

        $sumgrade = round($sumgrade, 2);
        if ($sumgrade != 1 and $data['comparestringspercentage'] > 0) {
            $errors["regexp_ts_answer[0]"] = get_string('invalidtssumvalue', 'qtype_writeregex');
        }

        // If test stings are set but they are not used.
        if ($answercount > 0 and $data['comparestringspercentage'] == 0 and $data['teststringshinttype'] == '0') {
            $errors['comparestringspercentage'] = get_string('invalidcomparets', 'qtype_writeregex');
        }

        // If test string hint is enabled but no test strings found.
        if ($data['teststringshinttype'] != '0' && $sumgrade == 0) {
            $errors['teststringshinttype'] = get_string('noteststringsforhint', 'qtype_writeregex');
        }

        return $errors;
    }

    /**
     * Validation values of compare values (public for testing).
     * @param $data array Data from form.
     * @param $errors array Errors array.
     * @return array Errors array.
     */
    public function validate_compare_values ($data, $errors) {

        $questiontype = new qtype_writeregex();
        $analyzers = $questiontype->available_analyzers();
        $sum = 0;

        foreach ($analyzers as $name => $description) {
            $constantstringname = 'compare' . $name . 'percentage';

            if ($data[$constantstringname] < 0 || $data[$constantstringname] > 100) {
                $errors[$constantstringname] = get_string('compareinvalidvalue', 'qtype_writeregex');
            }
            $sum += $data[$constantstringname];
        }

        if ($sum != 100 and !array_key_exists('comparetreepercentage', $errors)) {
            $errors['comparetreepercentage'] = get_string('invalidmatchingtypessumvalue', 'qtype_writeregex');
        }

        return $errors;
    }

    public function validate_dot_using_hints($data, $errors) {

        $hinttools = array('syntaxtreehinttype' => 'qtype_preg_syntax_tree_tool',
                            'explgraphhinttype' => 'qtype_preg_explaining_graph_tool');
        foreach ($hinttools as $hintname => $hinttool) {
            if ($data[$hintname] != 0) {
                $regexoptions = new qtype_preg_authoring_tools_options();
                $regexoptions->engine = $data['engine'];
                $regexoptions->usecase = $data['usecase'];
                $regexoptions->notation = $data['notation'];

                // Check possibility of using tool.
                try {
                    $tree = new $hinttool('.', $regexoptions);
                    $var = $tree->data_for_accepted_regex();
                } catch (Exception $e) {
                    $a = new stdClass;
                    $a->name = core_text::strtolower(get_string($tree->name(), 'qtype_preg'));
                    if (is_a($e, 'qtype_preg_pathtodot_empty')) {
                        $errors[$hintname] = get_string('pathtodotempty', 'qtype_preg', $a);
                    } else if (is_a($e, 'qtype_preg_pathtodot_incorrect')) {
                        $errors[$hintname] = get_string('pathtodotincorrect', 'qtype_preg', $a);
                    }
                    continue;
                }

                // Check possibility of using tool for concrete answers.
                $answers = $data['answer'];
                foreach ($answers as $key => $answer) {
                    if (array_key_exists('answer['.$key.']', $errors)) {
                        continue;
                    }
                    $tree = new $hinttool($answer, $regexoptions);
                    if (count($tree->get_errors()) > 0) {
                        $a = new stdClass;
                        $a->name = core_text::strtolower(get_string($tree->name(), 'qtype_preg'));
                        $a->index = $key + 1;
                        $errors[$hintname] = get_string('doterror', 'qtype_writeregex', $a);
                        break;
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Function which create hints array for form
     * @param object $question Question object.
     * @param bool $withclearwrong (do not use)
     * @param bool $withshownumpartscorrect (do not use)
     * @return object Question object.
     */
    protected function data_preprocessing_hints($question, $withclearwrong = false,
                                                $withshownumpartscorrect = false) {
        if (empty($question->hints)) {
            return $question;
        }

        $question = parent::data_preprocessing_hints($question, $withclearwrong, $withshownumpartscorrect);

        foreach ($question->hints as $hint) {

            $options = explode('\n', $hint->options);

            if (count($options) == 4) {

                $question->syntaxtreehint[] = $options[0];
                $question->explgraphhint[] = $options[1];
                $question->descriptionhint[] = $options[2];
                $question->teststringshint[] = $options[3];
            } else {
                $question->syntaxtreehint[] = 0;
                $question->explgraphhint[] = 0;
                $question->descriptionhint[] = 0;
                $question->teststringshint[] = 0;
            }
        }

        return $question;
    }

    /**
     * Get qtype name.
     * @return string Name of qtype.
     */
    public function qtype() {

        return 'writeregex';
    }
}
