<?php

define('MOODLE_INTERNAL', 1);
define('CLI_SCRIPT', 1);

// Load Moodle constants && PhpUnit classes
require_once('C:\\Users\\Admin\\Desktop\\____________\\server\\moodle\\config.php');
require_once($CFG->dirroot . '/vendor/autoload.php');

require_once($CFG->dirroot . '/question/type/preg/preg_matcher.php');
require_once($CFG->dirroot . '/question/type/preg/fa_matcher/fa_matcher.php');

$options = new qtype_preg_matching_options();
$options->approximatematch = true;
$options->typolimit = 4;
$options->mergeassertions = /*"0"*/ true;
//$options->exactmatch = false;
//$options->extensionneeded = true;
//$options->capturesubexpressions = false; /// LOL
$options->langid = null;
//$options->set_modifier(qtype_preg_matching_options::MODIFIER_CASELESS);
//$options->set_modifier(qtype_preg_matching_options::MODIFIER_MULTILINE);
//$options->set_modifier(qtype_preg_matching_options::MODIFIER_DOTALL);
// (a|b|c|d)(c|d|e|f)
//$matcher = new qtype_preg_fa_matcher("f(oo){1,2}((zup)*|(bar)*|(zap)*)*zot", $options);
//$str = 'fobrzot'; // "abbbcde"
$matcher = new qtype_preg_fa_matcher('(?=(?=a(b|))a[a-z]c)a[a-f]+', $options);
print_r($matcher->get_errors());
//print_r($matcher->automaton->fa_to_dot());
$str = 'adcaab'; // "abbbcde"
try {
    $result = $matcher->match($str);
} catch (Exception $e) {
    echo $e;
}
print_r($result);
print_r($matcher->automaton->fa_to_dot());
print_r($result->typos);
//print_r($result->typos->apply());
//$result = $result->typos->to_lexem_label_format();

//print_r($result);
//echo $str2 = $result->errors->apply($str) . "\n";
//qtype_preg_typo_container::substitution_as_deletion_and_insertion($result->errors);

//$str2 = $result->errors->apply($str, 0);
//$matcher->set_errors_limit(0);;
//$result = $matcher->match($str2);
//echo $str2;
//print_r($result);
////
//print_r($result);
//$tmp = $result->errors->apply($str);
//print_r($tmp);
//
//print_r(preg_match('/\\\\\d+/', '\123'));
