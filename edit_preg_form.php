<?php
// This file is part of Preg question type - https://bitbucket.org/oasychev/moodle-plugins/overview
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
 * Defines the editing form for the preg question type.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev <oasychev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/shortanswer/edit_shortanswer_form.php');
require_once($CFG->dirroot . '/blocks/formal_langs/block_formal_langs.php');
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_text_and_button.php');

/**
 * Preg editing form definition.
 */
class qtype_preg_edit_form extends qtype_shortanswer_edit_form {

    private $hintfields = [
        'charhint' => [
            'defaultpenlty' => '0.2',
            'enabled' => 0,
            'enginerequirements' => [qtype_preg_matcher::PARTIAL_MATCHING, qtype_preg_matcher::CORRECT_ENDING ],
        ],
        'lexemhint' => [
            'defaultpenlty' => '0.4',
            'enabled' => 0,
            'enginerequirements' => [qtype_preg_matcher::PARTIAL_MATCHING, qtype_preg_matcher::CORRECT_ENDING ],
        ],
        'howtofixpichint' => [
            'defaultpenlty' => '0.4',
            'enabled' => 0,
            'enginerequirements' => [ qtype_preg_matcher::FUZZY_MATCHING ],
        ],
    ];

    private $enabledspecificenginefields = [
        qtype_preg_matcher::PARTIAL_MATCHING => ['hintgradeborder', 'langid', 'lexemusername'],
        qtype_preg_matcher::CORRECT_ENDING => ['hintgradeborder', 'langid', 'lexemusername'],
        qtype_preg_matcher::FUZZY_MATCHING => ['approximatematch', 'maxtypos', 'typospenalty'],
    ];


    /**
     * This is overloaded method.
     * Get the list of form elements to repeat, one for each answer.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $repeatedoptions reference to array of repeated options to fill
     * @param $answersoption reference to return the name of $question->options
     *      field holding an array of answers
     * @return array of form fields.
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('hidden', 'regextests', '');
        $repeated[] = $mform->createElement('preg_text_and_button', 'answer', $label, 'regex_test');
        $repeated[] = $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback', get_string('feedback', 'question'),
            array('rows' => 5), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['regextests']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;
    }

    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $mform = $this->_form;
        list($repeated, $repeatedoptions) = parent::get_hint_fields($withclearwrong, $withshownumpartscorrect);

        $langselect = $mform->getElement('langid');
        $langs = $langselect->getSelected();
        $langobj = block_formal_langs::lang_object($langs[0]);
        $hintoptions = array(
            'hintmatchingpart' => get_string('hintbtn', 'qbehaviour_adaptivehints', get_string('hintcolouredstring', 'qtype_preg')),
            'hintnextchar' => get_string('hintbtn', 'qbehaviour_adaptivehints', get_string('hintnextchar', 'qtype_preg')),
            'hintnextlexem' => get_string('hintbtn', 'qbehaviour_adaptivehints', get_string('hintnextlexem', 'qtype_preg', $langobj->lexem_name())),
            'hinthowtofixpic' => get_string('hintbtn', 'qbehaviour_adaptivehints', get_string('hinthowtofixpic', 'qtype_preg'))
        );

        $repeated[] = $mform->createElement('select', 'interactivehint',
            get_string('hintbtn', 'qbehaviour_adaptivehints', ''), $hintoptions);
        return array($repeated, $repeatedoptions);
    }

    /**
     * Perform the necessary preprocessing for the hint fields.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_hints($question, $withclearwrong = false,
            $withshownumpartscorrect = false) {
        if (empty($question->hints)) {
            return $question;
        }
        $question = parent::data_preprocessing_hints($question, $withclearwrong, $withshownumpartscorrect);

        foreach ($question->hints as $hint) {
            $question->interactivehint[] = $hint->options;
        }

        return $question;
    }

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        global $CFG, $COURSE, $PAGE;

        question_bank::load_question_definition_classes($this->qtype());
        $qtypeclass = 'qtype_'.$this->qtype();
        $qtype = new $qtypeclass;

        $engines = $qtype->available_engines();

        $PAGE->requires->js('/question/type/poasquestion/ui-bootstrap/ui-bootstrap-tpls.min.js');
        $PAGE->requires->js('/question/type/poasquestion/bootstrap/js/bootstrap.min.js');
        $PAGE->requires->js('/question/type/poasquestion/bootstrap/js/bootstrap-tooltip.js');

        $mform->addElement('select', 'engine', get_string('engine', 'qtype_preg'), $engines);
        $mform->setDefault('engine', get_config('qtype_preg', 'defaultengine'));
        $mform->addHelpButton('engine', 'engine', 'qtype_preg');

        $notations = $qtype->available_notations();
        $mform->addElement('select', 'notation', get_string('notation', 'qtype_preg'), $notations);
        $mform->setDefault('notation', get_config('qtype_preg', 'defaultnotation'));
        $mform->addHelpButton('notation', 'notation', 'qtype_preg');

        // Fetch course context if it is possible.
        $context = null;
        if ($COURSE != null) {
            if (is_a($COURSE, 'stdClass')) {
                $context = context_course::instance($COURSE->id);
            } else {
                $context = $COURSE->get_context();
            }
        }

        $pickedlanguage = 0;
        if(is_object($this->question)) {
            if (isset($this->question->options)) {
                if (isset($this->question->options->langid)) {
                    $pickedlanguage = intval($this->question->options->langid);
                }
            }
        }
        $currentlanguages = block_formal_langs::available_langs( $context, $pickedlanguage );
        $mform->addElement('select', 'langid', get_string('langselect', 'qtype_preg'), $currentlanguages);
        $mform->setDefault('langid', get_config('qtype_preg', 'defaultlang'));
        $mform->addHelpButton('langid', 'langselect', 'qtype_preg');
        $mform->addElement('text', 'lexemusername', get_string('lexemusername', 'qtype_preg'), array('size' => 54));
        $mform->setDefault('lexemusername', '');
        $mform->addHelpButton('lexemusername', 'lexemusername', 'qtype_preg');
        $mform->setAdvanced('lexemusername');
        $mform->setType('lexemusername', PARAM_TEXT);

        $gradeoptions = question_bank::fraction_options();
        $mform->addElement('select', 'hintgradeborder', get_string('hintgradeborder', 'qtype_preg'), $gradeoptions);
        $mform->setDefault('hintgradeborder', 1);
        $mform->addHelpButton('hintgradeborder', 'hintgradeborder', 'qtype_preg');
        $mform->setAdvanced('hintgradeborder');

        $mform->addElement('selectyesno', 'exactmatch', get_string('exactmatch', 'qtype_preg'));
        $mform->addHelpButton('exactmatch', 'exactmatch', 'qtype_preg');
        $mform->setDefault('exactmatch', 1);

        $mform->addElement('text', 'correctanswer', get_string('correctanswer', 'qtype_preg'), array('size' => 54));
        $mform->addHelpButton('correctanswer', 'correctanswer', 'qtype_preg');
        $mform->setType('correctanswer', PARAM_RAW);

        // Set hint availability determined by engine capabilities.
        foreach ($engines as $engine => $enginename) {
            $questionobj = new qtype_preg_question;
            $querymatcher = $questionobj->get_query_matcher($engine);
            foreach ($this->enabledspecificenginefields as $option => $fields) {
                if (!$querymatcher->is_supporting($option)) {
                    foreach ($fields as $f) {
                        $mform->disabledIf($f, 'engine', 'eq', $engine);
                    }
                }
            }
        }

        parent::definition_inner($mform);

        $answersinstruct = $mform->getElement('answersinstruct');
        $answersinstruct->setText(get_string('answersinstruct', 'qtype_preg'));

    }

    /** Overload parent function to add other controls before answer fields.
     *  @param MoodleQuickForm $mform
     *  @param $label
     *  @param $gradeoptions
     *  @param $minoptions
     *  @param $addoptions
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
                                             $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        // Adding custom sections.
        $this->definition_additional_sections($mform);
        // Calling parent to actually add fields.
        parent::add_per_answer_fields($mform, $label, $gradeoptions, $minoptions, $addoptions);
    }

    /** Place additional sections on the form:
     * one section for each analyzer and a hinting options section.
     * @var MoodleQuickForm $mform
     */
    protected function definition_additional_sections(&$mform) {
        $qtypeclass = 'qtype_'.$this->qtype();
        $qtype = new $qtypeclass;
        $engines = $qtype->available_engines();

        // typos section
        $mform->addElement('header', 'typoanlyzinghdr', get_string('typoanalysis', 'qtype_preg'));
        $mform->addHelpButton('typoanlyzinghdr', 'typoanalysis', 'qtype_preg');
        $mform->addElement('selectyesno', 'approximatematch', get_string('approximatematch', 'qtype_preg'));
        $mform->addHelpButton('approximatematch', 'approximatematch', 'qtype_preg');
        $mform->setDefault('approximatematch', 1);
        $mform->addElement('text', 'maxtypos', get_string('maxtypos', 'qtype_preg'), array('size' => 3));
        $mform->setDefault('maxtypos', '2');
        $mform->setType('maxtypos', PARAM_INT);
        $mform->addHelpButton('maxtypos', 'maxtypos', 'qtype_preg');
        $mform->addElement('text', 'typospenalty', get_string('typospenalty', 'qtype_preg'), array('size' => 3));
        $mform->setDefault('typospenalty', '0.07');
        $mform->setType('typospenalty', PARAM_FLOAT);
        $mform->addHelpButton('typospenalty', 'typospenalty', 'qtype_preg');
        $mform->disabledIf('maxtypos', 'approximatematch', 'eq', 0);
        $mform->disabledIf('typospenalty', 'approximatematch', 'eq', 0);

        // Hinting section.
        $mform->addElement('header', 'hintinghdr', get_string('hinting', 'qtype_preg'));
        $mform->addHelpButton('hintinghdr', 'hinting', 'qtype_preg');
        foreach ($this->hintfields as $name => $params) {
            $mform->addElement('selectyesno', "use$name", get_string("use$name", 'qtype_preg'));
            $mform->setDefault("use$name", $params['enabled']);
            $mform->addHelpButton("use$name", "use$name", 'qtype_preg');
            $mform->addElement('text', "{$name}penalty", get_string('howtofixpichintpenalty', 'qtype_preg'), array('size' => 3));
            $mform->setDefault("{$name}penalty", $params['defaultpenlty']);
            $mform->setType("{$name}penalty", PARAM_FLOAT);
            $mform->addHelpButton("{$name}penalty", "{$name}penalty", 'qtype_preg');
            $mform->disabledIf("{$name}penalty", "use$name", 'eq', 0);

            // Set hint availability determined by engine capabilities.
            foreach ($engines as $engine => $enginename) {
                $questionobj = new qtype_preg_question;
                $querymatcher = $questionobj->get_query_matcher($engine);
                $issupporting = true;
                foreach ($params['enginerequirements'] as $option) {
                    $issupporting = $issupporting && $querymatcher->is_supporting($option);
                }

                if (!$issupporting) {
                    $mform->disabledIf("use$name", 'engine', 'eq', $engine);
                    $mform->disabledIf("{$name}penalty", 'engine', 'eq', $engine);
                }
            }
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $trimmedcorrectanswer = trim($data['correctanswer']);
        // If no correct answer is entered, we should think it is correct to not force teacher;
        // otherwise we must check that it match with at least one 100% grade answer.
        $correctanswermatch = ($trimmedcorrectanswer=='');
        $passhintgradeborder = false;
        $fractions = $data['fraction'];

        // Fill in some default data that could be absent due to disabling relevant form controls.
        if (!array_key_exists('hintgradeborder', $data)) {
            $data['hintgradeborder'] = 1;
        }
        foreach ($this->hintfields as $hintname => $_) {
            if (!array_key_exists("use$hintname", $data)) {
                $data["use$hintname"] = false;
            }
        }
        if (!array_key_exists('approximatematch', $data)) {
            $data['approximatematch'] = false;
        }
        if (!array_key_exists('maxtypos', $data)) {
            $data['maxtypos'] = 0;
        }

        $i = 0;
        question_bank::load_question_definition_classes($this->qtype());
        $questionobj = new qtype_preg_question;

        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $hintused = ($data['usecharhint'] || $data['uselexemhint'] || $data['usehowtofixpichint']) && $fractions[$key] >= $data['hintgradeborder'];
                // Create matcher to check regex for errors and try to match correct answer.
                $options = $questionobj->get_matching_options($data['exactmatch'], $questionobj->get_modifiers($data['usecase']), (-1)*$i, $data['notation'], $data['approximatematch']);
                $matcher = $questionobj->get_matcher($data['engine'], $trimmedanswer, $options, (-1)*$i, $hintused);
                if ($matcher->errors_exist()) {// There were errors in the matching process.
                    $regexerrors = $matcher->get_error_messages();// Show no more than max errors.
                    $errors['answer['.$key.']'] = '';
                    foreach ($regexerrors as $regexerror) {
                        $errors['answer['.$key.']'] .= $regexerror.'<br />';
                    }
                } else if ($trimmedcorrectanswer != '' && $data['fraction'][$key] == 1) {
                    // Correct answer (if supplied) should match at least one 100% grade answer.
                    if ($matcher->match($trimmedcorrectanswer)->full) {
                        $correctanswermatch=true;
                    }
                }
                if ($fractions[$key] >= $data['hintgradeborder']) {
                    $passhintgradeborder = true;
                }
            }
            $i++;
        }

        if ($correctanswermatch == false) {
            $errors['correctanswer'] = get_string('nocorrectanswermatch', 'qtype_preg');
        }

        if ($passhintgradeborder == false && $data['usecharhint']) {// No answer pass hint grade border.
            $errors['hintgradeborder'] = get_string('nohintgradeborderpass', 'qtype_preg');
        }

        $querymatcher = $questionobj->get_query_matcher($data['engine']);
        // If engine doesn't support subexpression capturing, than no placeholders should be in feedback.
        if (!$querymatcher->is_supporting(qtype_preg_matcher::SUBEXPRESSION_CAPTURING)) {
            $feedbacks = $data['feedback'];
            foreach ($feedbacks as $key => $feedback) {
                if (is_array($feedback)) {// On some servers feedback is HTMLEditor, on another it is simple text area.
                    $feedback = $feedback['text'];
                }
                if (!empty($feedback) && preg_match('/\{\$([1-9][0-9]*|\w+)\}/', $feedback) == 1) {
                    $errors['feedback['.$key.']'] = get_string('nosubexprcapturing', 'qtype_preg', $querymatcher->name());
                }
            }
        }

        // Check that interactive hint settings doesn't contradict overall hint settings.
        $interactivehints = $data['interactivehint'];
        foreach($interactivehints as $key => $hint) {
            if ($hint == 'hintnextchar' && $data['usecharhint'] != true) {
                $errors['interactivehint['.$key.']'] = get_string('unallowedhint', 'qtype_preg', get_string('hintnextchar', 'qtype_preg'));
            }
            if ($hint == 'hintnextlexem' && $data['uselexemhint'] != true) {
                $langs = block_formal_langs::available_langs();
                $langobj = block_formal_langs::lang_object(array_keys($langs)[0]);
                $errors['interactivehint['.$key.']'] = get_string('unallowedhint', 'qtype_preg', get_string('hintnextlexem', 'qtype_preg', $langobj->lexem_name()));
            }
            if ($hint == 'hinthowtofixpic' && $data['usehowtofixpichint'] != true) {
                $errors['interactivehint['.$key.']'] = get_string('unallowedhint', 'qtype_preg', get_string('hinthowtofixpic', 'qtype_preg'));
            }
        }

        // how to fix hint should be available only if approximate math is enabled
        if ($data['usehowtofixpichint'] && (!$data['approximatematch'] || $data['maxtypos'] == 0)) {
            $errors['usehowtofixpichint'] = get_string('noapproximateforhowtofixpichint', 'qtype_preg');
        }

        return $errors;
    }

    public function qtype() {
        return 'preg';
    }

}
