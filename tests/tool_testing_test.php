<?php

/**
 * Unit tests for explain graph tool.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Terechov Grigory <grvlter@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
ob_start();
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_regex_testing_tool_loader.php');
ob_end_clean();

class qtype_preg_tool_testing_test extends PHPUnit\Framework\TestCase {

    function test_loader_no_selection() {
        $_GET['regex'] = 'a';
        $_GET['engine'] = 'fa_matcher';
        $_GET['notation'] = 'native';
        $_GET['exactmatch'] = 0;
        $_GET['usecase'] = 0;
        $_GET['indfirst'] = -2;
        $_GET['indlast'] = -2;
        $_GET['strings'] = 'a';
        $_GET['ajax'] = 1;

        $json = qtype_preg_get_json_array();
        $this->assertEquals(-2, $json['indfirst']);
        $this->assertEquals(-2, $json['indlast']);
    }

    function test_loader_selection() {
        $_GET['regex'] = 'a';
        $_GET['engine'] = 'fa_matcher';
        $_GET['notation'] = 'native';
        $_GET['exactmatch'] = 0;
        $_GET['usecase'] = 0;
        $_GET['indfirst'] = 0;
        $_GET['indlast'] = 0;
        $_GET['strings'] = 'a';
        $_GET['ajax'] = 1;

        $json = qtype_preg_get_json_array();
        $this->assertEquals(0, $json['indfirst']);
        $this->assertEquals(0, $json['indlast']);
    }

    function test_loader_exact_selection() {
        $_GET['regex'] = 'a';
        $_GET['engine'] = 'fa_matcher';
        $_GET['notation'] = 'native';
        $_GET['exactmatch'] = 1;
        $_GET['usecase'] = 0;
        $_GET['indfirst'] = 0;
        $_GET['indlast'] = 0;
        $_GET['strings'] = 'a';
        $_GET['ajax'] = 1;

        $json = qtype_preg_get_json_array();
        $this->assertEquals(0, $json['indfirst']);
        $this->assertEquals(0, $json['indlast']);
    }

    function test_correct() {
        $regex = 'a|b';
        $strings = "a\nb";
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span></span><br /><span id="qtype-preg-colored-string"><span class="correct">b</span></span><br />', $str);
    }

    function test_empty_strings() {
        $regex = 'a|b';
        $strings = '';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<br />', $str);
    }

    function test_syntax_error() {
        $regex = 'smile! :)';
        $strings = ':/';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<br />smile! :<b>)</b><br/>Syntax error: missing opening parenthesis \'(\' for the closing parenthesis in position 8', $str);
    }

    function test_accepting_error() {
        $regex = '(?=some day this will be supported)...';
        $strings = 'wat';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<br /><b>(?=some day this will be supported)</b>...<br/>Positive lookahead assert in position from 0:0 to 0:34 is not supported by finite state automata.', $str);
    }

    function test_empty_regex() {
        $regex = '';
        $strings = '';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('', $str);

        $strings = "a|b";
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position());
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('', $str);
    }

    function test_selection_dummy() {
        $regex = 'a';
        $strings = 'a';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(0, 0));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="qtype-preg-selection"><span class="correct">a</span></span></span><br />', $str);
    }

    function test_selection_grouping() {
        $regex = 'a(?:bc)d';
        $strings = 'abcd';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 6));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bc</span></span><span class="correct">d</span></span><br />', $str);

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bc</span></span><span class="correct">d</span></span><br />', $str);

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(6, 6));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bc</span></span><span class="correct">d</span></span><br />', $str);
    }

    function test_selection_non_preserved() {
        $regex = 'a(?i)b';
        $strings = 'ab';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 4));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">ab</span></span><br />', $str);

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(0, 4));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="qtype-preg-selection"><span class="correct">a</span></span><span class="correct">b</span></span><br />', $str);

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 5));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b</span></span></span><br />', $str);
    }

    function test_selection_partial_match() {
        $regex = '^a(bc)def';
        $strings = 'abc';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';

        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bc</span></span>...</span><br />', $str);

        $strings = 'ab';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b</span></span>...</span><br />', $str);

        $strings = 'a';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span>...</span><br />', $str);

        $strings = '';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5));
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<br />', $str);
    }

    function test_approximate_match_with_simple_regex() {
        $regex = '^a(bc)def';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';
        $typolimit = 2;

        // selection "(bc)", missing 'c'
        $strings = 'abdef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b<span class="partiallycorrect">…</span></span></span><span class="correct">def</span></span><br />', $str);

        // selection "(bc)", missing 'b' and 'c'
        $strings = 'adef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span><span class="partiallycorrect">…</span></span></span><span class="correct">def</span></span><br />', $str);

        // selection "(bc)", missing 'a' and 'c'
        $strings = 'bdef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct"><span class="partiallycorrect">…</span></span><span class="qtype-preg-selection"><span class="correct">b<span class="partiallycorrect">…</span></span></span><span class="correct">def</span></span><br />', $str);

        // selection "(bc)", missing 'b' and 'd'
        $strings = 'acef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span>c</span></span><span class="correct"><span class="partiallycorrect">…</span>ef</span></span><br />', $str);

        // selection "a", missing 'a'
        $strings = 'bcdef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct">bcdef</span></span><br />', $str);

        // selection "(bc)", transposed 'c' and 'd'
        $strings = 'abdcef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b<span class="partiallycorrect">d</span></span></span><span class="correct"><span class="partiallycorrect">c</span>ef</span></span><br />', $str);

        // selection "(bc)", 2 redundant 'b'
        $strings = 'abbbcdef';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b<span class="incorrect">b</span><span class="incorrect">b</span>c</span></span><span class="correct">def</span></span><br />', $str);

        // selection "(bc)", 'c' was replaced with '_'
        $strings = 'ab_def';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b<span class="incorrect">_</span><span class="partiallycorrect">…</span></span></span><span class="correct">def</span></span><br />', $str);

        // selection "(bc)", 'b' & 'c' was replaced with '_'
        $strings = 'a__def';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(2, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct"><span class="incorrect">_</span><span class="partiallycorrect">…</span><span class="incorrect">_</span><span class="partiallycorrect">…</span></span></span><span class="correct">def</span></span><br />', $str);

    }

    function test_approximate_match_with_regex_with_quant() {
        $regex = 'ab{3,}cde';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';
        $typolimit = 3;

        // selection "b{3,}", missing all 'b'
        $strings = 'acde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span><span class="partiallycorrect">…</span><span class="partiallycorrect">…</span></span></span><span class="correct">cde</span></span><br />', $str);

        // selection "b{3,}", missing single 'b'
        $strings = 'abbcde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bb<span class="partiallycorrect">…</span></span></span><span class="correct">cde</span></span><br />', $str);

        // selection "b", missing all 'b'
        $strings = 'acde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a<span class="partiallycorrect">…</span><span class="partiallycorrect">…</span></span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct">cde</span></span><br />', $str);

        // selection "b", missing single 'b'
        $strings = 'abbcde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">abb</span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct">cde</span></span><br />', $str);

        // selection "b", missing 'b' & 'c'
        $strings = 'abbde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">abb</span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct"><span class="partiallycorrect">…</span>de</span></span><br />', $str);

        // selection "b{3,}", missing 'b' & 'c'
        $strings = 'abbde';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">bb<span class="partiallycorrect">…</span></span></span><span class="correct"><span class="partiallycorrect">…</span>de</span></span><br />', $str);

        // selection "b", missing "bbbcd"
        $typolimit = 6;
        $strings = 'ae';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a<span class="partiallycorrect">…</span><span class="partiallycorrect">…</span></span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct"><span class="partiallycorrect">…</span><span class="partiallycorrect">…</span>e</span></span><br />', $str);
    }

    function test_approximate_match_with_regex_with_complex_quant() {
        $regex = '(fooo)+(baar)+';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';
        $typolimit = 3;

        $strings = 'foobrbaar';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(4, 4), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">foo<span class="incorrect">b</span></span><span class="qtype-preg-selection"><span class="correct"><span class="incorrect">r</span><span class="partiallycorrect">…</span></span></span><span class="correct">baar</span></span><br />', $str);

        $strings = 'fooobarbaar';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(9, 10), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">foooba<span class="partiallycorrect">…</span>rb</span><span class="qtype-preg-selection"><span class="correct">aa</span></span><span class="correct">r</span></span><br />', $str);

        $strings = 'fooobarbaar';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(7, 13), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">fooo</span><span class="qtype-preg-selection"><span class="correct">ba<span class="partiallycorrect">…</span>rbaar</span></span></span><br />', $str);

        $strings = 'fooboaar';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(7, 13), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">foo<span class="partiallycorrect">b</span></span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">o</span>aar</span></span></span><br />', $str);

    }

    function test_approximate_partial_match() {
        $regex = 'ab{3,}cde';
        $usecase = false;
        $exactmatch = false;
        $engine = 'fa_matcher';
        $notation = 'native';
        $typolimit = 2;

        // selection "b{3,}", str "abc"
        $strings = 'abc';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 5), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">a</span><span class="qtype-preg-selection"><span class="correct">b<span class="partiallycorrect">…</span><span class="partiallycorrect">…</span></span></span><span class="correct">c</span>...</span><br />', $str);

        // selection "b", str "abc"
        $strings = 'abc';
        $tool = new qtype_preg_regex_testing_tool($regex, $strings, $usecase, $exactmatch, $engine, $notation, new qtype_preg_position(1, 1), true, $typolimit);
        $json = $tool->generate_json();
        $str = strip_tags($json['regex_test'], '<span><br><b>');
        $this->assertEquals('<span id="qtype-preg-colored-string"><span class="correct">ab<span class="partiallycorrect">…</span></span><span class="qtype-preg-selection"><span class="correct"><span class="partiallycorrect">…</span></span></span><span class="correct">c</span>...</span><br />', $str);

    }
 }
