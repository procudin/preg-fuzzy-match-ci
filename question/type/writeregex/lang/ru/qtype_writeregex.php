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
 * Strings for component 'qtype_writeregex', language 'ru'
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['answer'] = 'Демонстрация для правильного ответа';
$string['both'] = 'Демонстрация для ответа студента и правильного ответа (оба)';
$string['compareautomataanalyzername'] = 'Анализатор конечных автоматов';
$string['compareautomatapercentage'] = 'Оценка по свопадению автоматов регулярных выражений';
$string['compareautomatapercentage_help'] = "<p>Значение (в %) доли оценки по совпадению автоматов регулярных выражений .</p>";
$string['compareinvalidvalue'] = 'Значение должно быть в диапазоне от 0 до 100';
$string['comparestringsanalyzername'] = 'Анализатор тестовых строк';
$string['comparestringspercentage'] = 'Оценка по проверке на тестовых строках регулярных выражений';
$string['comparestringspercentage_help'] = "<p>Значение (в %) доли оценки по проверке на тестовых строках регулярных выражений .</p>";
$string['comparetreepercentage'] = 'Оценка по свопадению регулярных выражений';
$string['comparetreepercentage_help'] = "<p>Значение (в %) доли оценки по совпадению регулярных выражений .</p>";
$string['descriptionhintpenalty'] = 'Объяснение выражения: штраф';
$string['descriptionhintpenalty_help'] = "<p>Величина штрафа за использование подсказки в виде объяснения выражения.</p>";
$string['descriptionhinttype'] = 'Объяснение выражения';
$string['descriptionhinttype_help'] = "<p>Значение отображения подсказки в виде объяснения выражения.</p>";
$string['doterror'] = 'Невозможно отрисовать {$a->name} для регулярного выражения №{$a->index}';
$string['explgraphhintpenalty'] = 'Граф объяснения: штраф';
$string['explgraphhintpenalty_help'] = "<p>Величина штрафа за использование подсказки в виде графа объяснения.</p>";
$string['explgraphhinttype'] = 'Граф объяснения';
$string['explgraphhinttype_help'] = "<p>Значение отображения подсказки в виде графа объяснения.</p>";
$string['filloutoneanswer'] = 'You must provide at least one possible answer. Answers left blank will not be used. \'*\' can be used as a wildcard to match any characters. The first matching answer will be used to determine the score and feedback.';
$string['hintdescriptionstudentsanswer'] = "Ваш ответ";
$string['hintdescriptionteachersanswer'] = "Правильный ответ";
$string['hintexplanation'] = '{$a->type} {$a->mode}:';
$string['hintsheader'] = 'Подсказки';
$string['hinttitleaddition'] = '(для {$a})';
$string['hinttitleadditionformode_1'] = 'Вашего ответа';
$string['hinttitleadditionformode_2'] = 'правильного ответа';
$string['hinttitleadditionformode_3'] = 'Вашего и правильного ответов';
$string['invalidcomparets'] = 'Значение проверки по тестовым строкам выставлена в 0, удалите тестовые строки';
$string['invalidmatchingtypessumvalue'] = 'Сумма всех типов проверок не равна 100%';
$string['invalidtssumvalue'] = 'Сумма оценок строк должна иметь значение 100, т. к. высталена проверка по ним';
$string['none'] = 'Не показывать';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['noteststringsforhint'] = 'Отсутствуют тестовые строки для подсказки';
$string['penalty'] = 'Штраф';
$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pluginname'] = 'Write RegEx';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent types a word or short phrase. There may be several possible correct answers, each with a different grade. If the "Case sensitive" option is selected, then you can have different scores for "Word" or "word".';
$string['pluginname_link'] = 'question/type/writeregex';
$string['pluginnameadding'] = 'Добавить вопрос Write RegEx';
$string['pluginnameediting'] = 'Изменение вопроса Write RegEx';
$string['pluginnamesummary'] = 'Вопрос для контроля знаний студентов по составлению регулярных выражений (regexp).';
$string['regexp_answers'] = "Регулярное\nвыражение {no}";
$string['regexp_ts'] = 'Тестовая строка {no}';
$string['regexp_ts_header'] = 'Тестовые строки';
$string['student'] = 'Демонстрация для ответа студента';
$string['syntaxtreehintpenalty'] = 'Синтаксическое дерево: штраф';
$string['syntaxtreehintpenalty_help'] = "<p>Значение штрафа за использование подсказки в виде синтаксического дерева</p>";
$string['syntaxtreehinttype'] = 'Синтаксическое дерево';
$string['syntaxtreehinttype_help'] = "<p>Значение отображения подсказки в виде синтаксического дерева</p>";
$string['teststringshintexplanation'] = 'Результаты совпадения {$a} с тестовыми строками:';
$string['teststringshintpenalty'] = 'Тестовые строки: штраф';
$string['teststringshintpenalty_help'] = "<p>Величина штрафа за использование подсказки в виде тестовых строк.</p>";
$string['teststringshinttype'] = 'Тестовые строки';
$string['teststringshinttype_help'] = "<p>Значение отображения подсказки в виде тестовых строк.</p>";
$string['unavailableautomataanalyzer'] = 'При использовании данного движка невозможно использовать анализатор на автоматах';

$string['extracharactermismatchfrombeginning'] = 'В Вашем ответе в отличие от правильного возможно совпадение символа \'{$a->character}\' с начала строки';
$string['missingcharactermismatchfrombeginning'] = 'В Вашем ответе в отличие от правильного невозможно совпадение символа \'{$a->character}\' с начала строки';
$string['extracharactermismatch'] = 'В Вашем ответе в отличие от правильного после совпадения строки \'{$a->matchedstring}\' возможно совпадение символа \'{$a->character}\'';
$string['missingcharactermismatch'] = 'В Вашем ответе в отличие от правильного после совпадения строки \'{$a->matchedstring}\' невозможно совпадение символа \'{$a->character}\'';
$string['extrafinalstatemismatch'] = 'Ваш ответ в отличие от правильного допускает строку \'{$a}\'';
$string['missingfinalstatemismatch'] = 'Ваш ответ в отличие от правильного не допускает строку \'{$a}\'';
$string['start'] = 'начинаются';
$string['starts'] = 'начинается';
$string['end'] = 'заканчиваются';
$string['ends'] = 'заканчивается';
$string['youranswer'] = 'Вашем ответе';
$string['theporrectanswer'] = 'правильном ответе';
$string['aftermatchedstring'] = 'после строки \'{$a}\'';
$string['frombeginning'] = 'с начала строки';
$string['singlesubpatternmismatch'] = 'Подмаска №{$a->subpatterns} {$a->behavior} в {$a->matchedanswer} при совпадении символа \'{$a->character}\' {$a->place}, тогда как в {$a->mismatchedanswer} это не так';
$string['multiplesubpatternsmismatch'] = 'Подмаски №{$a->subpatterns} {$a->behavior} в {$a->matchedanswer} при совпадении символа \'{$a->character}\' {$a->place}, тогда как в {$a->mismatchedanswer} это не так';
$string['nosubpatternmismatch'] = 'В {$a->matchedanswer} символ \'{$a->character}\' {$a->place} совпадает без подмасок, тогда как в {$a->mismatchedanswer} такое невозможено';