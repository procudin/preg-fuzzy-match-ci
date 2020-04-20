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
 * Strings for component 'qtype_writeregex', language 'en'
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['answer'] = 'Show the correct answer';
$string['both'] = 'Show the student\'s answer and the correct answer (both)';
$string['compareautomataanalyzername'] = 'Automata analyzer';
$string['compareautomatapercentage'] = 'Rating coincidentally automata regular expressions';
$string['compareautomatapercentage_help'] = "<p>Value (in%) of the share estimates coincidentally automata regular expressions.</p>";
$string['compareinvalidvalue'] = 'The value must be in the range from 0 to 100';
$string['comparestringsanalyzername'] = 'Test strings analyzer';
$string['comparestringspercentage'] = 'Based on testing on the test lines of regular expressions';
$string['comparestringspercentage_help'] = "<p>Value (in%) of the share valuation verification test on the lines of regular expressions.</p>";
$string['comparetreepercentage'] = 'Based on regular expression match';
$string['comparetreepercentage_help'] = "<p>The value (in%) of the share estimates for regular expression matching.</p>";
$string['descriptionhintpenalty'] = 'Explanation of the expression: penalty';
$string['descriptionhintpenalty_help'] = "<p>The amount of penalty for using hints as explanations of expression.</p>";
$string['descriptionhinttype'] = 'Explanation of the expression';
$string['descriptionhinttype_help'] = "<p>Display value in the form of tips explaining expression.</p>";
$string['doterror'] = 'Can\'t draw {$a->name} for regex #{$a->index}';
$string['explgraphhintpenalty'] = 'Explanation graph: penalty';
$string['explgraphhintpenalty_help'] = "<p>The amount of penalty for the use of tips as a graph explanation.</p>";
$string['explgraphhinttype'] = 'Explanation graph';
$string['explgraphhinttype_help'] = "<p>Value to display a tooltip as a graph explanation.</p>";
$string['filloutoneanswer'] = 'You must provide at least one possible answer. Answers left blank will not be used. \'*\' can be used as a wildcard to match any characters. The first matching answer will be used to determine the score and feedback.';
$string['hintdescriptionstudentsanswer'] = "Your answer";
$string['hintdescriptionteachersanswer'] = "Correct answer";
$string['hintexplanation'] = '{$a->type} {$a->mode}:';
$string['hintsheader'] = 'Hints';
$string['hinttitleaddition'] = '({$a})';
$string['hinttitleadditionformode_1'] = 'for your answer';
$string['hinttitleadditionformode_2'] = 'for correct answer';
$string['hinttitleadditionformode_3'] = 'for your and correct answers';
$string['invalidcomparets'] = 'Check value for the test string is set to 0, remove the test strings';
$string['invalidmatchingtypessumvalue'] = 'Sum of all matching types is not equal 100%';
$string['invalidtssumvalue'] = 'Sum fractions of lines must be set to 100';
$string['none'] = 'None';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['noteststringsforhint'] = 'There are no test strings for hint';
$string['penalty'] = 'Penalty';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pluginname'] = 'Write RegEx';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent types a word or short phrase. There may be several possible correct answers, each with a different grade. If the "Case sensitive" option is selected, then you can have different scores for "Word" or "word".';
$string['pluginname_link'] = 'question/type/writeregex';
$string['pluginnameadding'] = 'Adding a Write RegEx question';
$string['pluginnameediting'] = 'Editing a Write RegEx question';
$string['pluginnamesummary'] = 'Question to monitor student\'s knowledge of compiling regular expressions (regexp).';
$string['regexp_answers'] = 'Regular expression {no}';
$string['regexp_ts'] = 'Test string {no}';
$string['regexp_ts_header'] = 'Test strings';
$string['student'] = 'Show the student\'s answer';
$string['syntaxtreehintpenalty'] = 'Syntax tree: penalty';
$string['syntaxtreehintpenalty_help'] = "<p>Meaning usage penalty hints as syntax tree</p>";
$string['syntaxtreehinttype'] = 'Syntax tree';
$string['syntaxtreehinttype_help'] = "<p>Value display hints as syntax tree.</p>";
$string['teststringshintexplanation'] = 'Test strings match results {$a}:';
$string['teststringshintpenalty'] = 'Test string: penalty';
$string['teststringshintpenalty_help'] = "<p>The amount of penalty for the use of clues in the form of test strings.</p>";
$string['teststringshinttype'] = 'Test string';
$string['teststringshinttype_help'] = "<p>Value display clues in the form of test strings.</p>";
$string['unavailableautomataanalyzer'] = 'You can\'t use automata analyzer with this engine';

$string['extracharactermismatchfrombeginning'] = 'Your answer accepts character \'{$a->character}\' at the beginning while the correct one doesn\'t';
$string['missingcharactermismatchfrombeginning'] = 'Your answer doesn\'t accept character \'{$a->character}\'  at the beginning while the correct one does';
$string['extracharactermismatch'] = 'Your answer accepts character \'{$a->character}\' after matching the string \'{$a->matchedstring}\' while the correct one doesn\'t';
$string['missingcharactermismatch'] = 'Your answer doesn\'t accept character \'{$a->character}\' after matching the string \'{$a->matchedstring}\' while the correct one does';
$string['extrafinalstatemismatch'] = 'Your answer accepts the string \'{$a}\' while the correct one doesn\'t';
$string['missingfinalstatemismatch'] = 'Your answer doesn\'t accept the string \'{$a}\' while the correct one does';
$string['start'] = 'start';
$string['starts'] = 'starts';
$string['end'] = 'end';
$string['ends'] = 'ends';
$string['youranswer'] = 'your answer';
$string['theporrectanswer'] = 'the correct answer';
$string['aftermatchedstring'] = 'after string \'{$a}\'';
$string['frombeginning'] = 'from beginning of the string';
$string['singlesubpatternmismatch'] = 'Subpattern #{$a->subpatterns} {$a->behavior} in {$a->matchedanswer} after matching character \'{$a->character}\' {$a->place} while in {$a->mismatchedanswer} it doesn\'t';
$string['multiplesubpatternsmismatch'] = 'Subpatterns #{$a->subpatterns} {$a->behavior} in {$a->matchedanswer} after matching character \'{$a->character}\' {$a->place} while in {$a->mismatchedanswer} they don\'t';
$string['nosubpatternmismatch'] = '{$a->matchedanswer} accepts character \'{$a->character}\' {$a->place} without any subpattern while {$a->mismatchedanswer} doesn\'t';