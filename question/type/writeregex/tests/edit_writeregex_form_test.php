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
 * Unit tests for question/type/writeregex/edit_writeregex_form.php
 *
 * @package    qtype
 * @subpackage writeregex
 * @copyright  2015 Oleg Sychev, Volgograd State Technical University
 * @author     Kamo Spertsian <spertsiankamo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/writeregex/questiontype.php');
require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/writeregex/edit_writeregex_form.php');

class qtype_writeregex_edit_writeregex_form_test extends advanced_testcase {

    // Question type object.
    protected $qtype;
    // Id of the saved question.
    protected $questionid;
    // Original question data.
    protected $questiondata;
    // Category
    protected $cat;
    // Question edit form
    protected $form;

    /**
     * Not using setUp for now to avoid looking for
     * how resetAfterTest(true) interact with it.
     */
    protected function setup_db_question() {

        $this->questiondata = test_question_maker::get_question_data('writeregex', 'one_regex_two_teststings');
        $formdata = test_question_maker::get_question_form_data('writeregex', 'one_regex_two_teststings');

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->cat = $generator->create_question_category(array());

        $formdata->category = "{$this->cat->id},{$this->cat->contextid}";
        qtype_writeregex_edit_form::mock_submit((array)$formdata);

        $this->form = qtype_writeregex_test_helper::get_question_editing_form($this->cat, $this->questiondata);
    }

    public function test_validate_compare_values() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->setup_db_question();

        // Test single analyzer mode.
        $data = array('comparetreepercentage' => 0, 'compareautomatapercentage' => 0, 'comparestringspercentage' => 100);
        $errors = array();
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(empty($errors));

        // Test two analyzer modes.
        $data = array('comparetreepercentage' => 0, 'compareautomatapercentage' => 50, 'comparestringspercentage' => 50);
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(empty($errors));

        // Test three analyzer modes with same percentages.
        $data = array('comparetreepercentage' => 33.33, 'compareautomatapercentage' => 33.33, 'comparestringspercentage' => 33.34);
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(empty($errors));

        // Test three analyzer modes with different percentages.
        $data = array('comparetreepercentage' => 20, 'compareautomatapercentage' => 30, 'comparestringspercentage' => 50);
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(empty($errors));

        // Test percentages sum less then 100.
        $data = array('comparetreepercentage' => 20, 'compareautomatapercentage' => 30, 'comparestringspercentage' => 40);
        $errors = array();
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(array_key_exists('comparetreepercentage', $errors));

        // Test percentages sum more then 100.
        $data = array('comparetreepercentage' => 20, 'compareautomatapercentage' => 30, 'comparestringspercentage' => 60);
        $errors = array();
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(array_key_exists('comparetreepercentage', $errors));

        // Test single percentage less then 0.
        $data = array('comparetreepercentage' => 20, 'compareautomatapercentage' => -40, 'comparestringspercentage' => 40);
        $errors = array();
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(array_key_exists('compareautomatapercentage', $errors));

        // Test single percentage more then 100.
        $data = array('comparetreepercentage' => 150, 'compareautomatapercentage' => 30, 'comparestringspercentage' => 60);
        $errors = array();
        $errors = $this->form->validate_compare_values($data, $errors);
        $this->assertTrue(array_key_exists('comparetreepercentage', $errors));
    }

    public function test_validate_test_strings() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->setup_db_question();

        $data = array();
        $data['regexp_ts_answer'] = array('0', '1', '2', '3');

        // Test correct teststrings fractions.
        $data['regexp_ts_fraction'] = array(0.5, 0.3, 0.1, 0.1);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(empty($errors));

        // Test correct and same teststrings fractions.
        $data['regexp_ts_fraction'] = array(0.25, 0.25, 0.25, 0.25);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(empty($errors));

        // Test sum teststrings fractions less then 1.
        $data['regexp_ts_fraction'] = array(0.5, 0.1, 0.1, 0.1);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(array_key_exists('regexp_ts_answer[0]', $errors));

        // Test sum teststrings fractions more then 1.
        $data['regexp_ts_fraction'] = array(0.5, 0.4, 0.1, 0.1);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(array_key_exists('regexp_ts_answer[0]', $errors));

        // Test correct single teststring fraction.
        $data['regexp_ts_answer'] = array('0');
        $data['regexp_ts_fraction'] = array(1);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(empty($errors));

        // Test single teststring fraction more then 1.
        $data['regexp_ts_fraction'] = array(1.6);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(array_key_exists('regexp_ts_answer[0]', $errors));

        // Test single teststring fraction less then 1.
        $data['regexp_ts_fraction'] = array(0.4);
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 100);
        $this->assertTrue(array_key_exists('regexp_ts_answer[0]', $errors));

        // Test teststrings exist but analyzer turned off.
        $errors = array();
        $errors = $this->form->validate_test_strings($data, $errors, 0);
        $this->assertTrue(array_key_exists('comparestringspercentage', $errors));
    }

    public function test_validate_regexp() {
        global $CFG;
        $CFG->qtype_writregex_maxerrorsshown = 5;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->setup_db_question();

        $data = array('comparestringspercentage' => 100,
                        'engine' => 'fa_matcher',
                        'notation' => 'native',
                        'usecase' => 1);

        // Test single correct regex.
        $data['answer'] = array('...');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(empty($errors));

        // Test some correct regexes.
        $data['answer'] = array('...', 'abc', 'qwe');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(empty($errors));

        // Test single incorrect regex.
        $data['answer'] = array('..[');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(array_key_exists('answer[0]', $errors));

        // Test single incorrect regex in some answers.
        $data['answer'] = array('...', '..[', 'qwe');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(array_key_exists('answer[1]', $errors));

        // Test some incorrect regexes in some answers.
        $data['answer'] = array('...', '..[', 'q[e');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(array_key_exists('answer[1]', $errors) && array_key_exists('answer[2]', $errors));

        // Test all incorrect regexes in some answers.
        $data['answer'] = array('[..', '..[', 'q[e');
        $errors = array();
        $errors = $this->form->validate_regexp($data, $errors);
        $this->assertTrue(array_key_exists('answer[0]', $errors) && array_key_exists('answer[1]', $errors) && array_key_exists('answer[2]', $errors));
    }

    public function test_validate_dot_using_hints() {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->setup_db_question();

        $data = array('engine' => 'fa_matcher',
                        'notation' => 'native',
                        'usecase' => 1,
                        'answer' => array(),
                        'syntaxtreehinttype' => 1,
                        'explgraphhinttype' => 0);

        $a = new stdClass;
        $a->name = core_text::strtolower(get_string('syntax_tree_tool', 'qtype_preg'));

        // Test no path to dot set.
        $CFG->pathtodot = '';
        $errors = array();
        $errors = $this->form->validate_dot_using_hints($data, $errors);
        $experrors = array('syntaxtreehinttype' => get_string('pathtodotempty', 'qtype_preg', $a));
        $this->assertTrue($errors == $experrors);

        // Test incorrect path to dot.
        $CFG->pathtodot = 'C';
        $errors = array();
        $errors = $this->form->validate_dot_using_hints($data, $errors);
        $experrors = array('syntaxtreehinttype' => get_string('pathtodotincorrect', 'qtype_preg', $a));
        $this->assertTrue($errors == $experrors);
    }
}
