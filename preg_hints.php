<?php
// This file is part of Preg question type - https://bitbucket.org/oasychev/moodle-plugins/overview
//
// Preg question type is free software: you can redistribute it and/or modify
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
 * Defines Preg question type hints classes.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev <oasychev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/preg_matcher.php');
require_once($CFG->dirroot . '/blocks/formal_langs/block_formal_langs.php');
require_once($CFG->dirroot . '/blocks/formal_langs/mistakesimage.php');


/**
 * Hint class for showing matching part of a response (along with unmatched head and tail).
 * Also contains some methods common to the all hints, based on $matchresults.
 */
class qtype_preg_hintmatchingpart extends qtype_poasquestion\hint {

    public function hint_type() {
        return qtype_poasquestion\hint::SINGLE_INSTANCE_HINT;
    }

    public function hint_description() {
        return get_string('hintcolouredstring', 'qtype_preg');
    }

    /**
     * Is hint based on response or not?
     *
     * @return boolean true if response is used to calculate hint (and, possibly, penalty).
     */
    public function hint_response_based() {
        return true;// All matching hints are based on the response.
    }

    /**
     * Returns whether response allows for the hint to be done.
     */
    public function hint_available($response = null) {
        if ($response !== null) {
            $bestfit = $this->question->get_best_fit_answer($response);
            $matchresults = $bestfit['match'];
            return $this->could_show_hint($matchresults, false) /*&& $bestfit['answer']->fraction >= $this->question->hintgradeborder*/;
        }
        return false;
    }

    /**
     * Returns penalty for using specific hint of given hint type (possibly for given response).
     */
    public function penalty_for_specific_hint($response = null) {
            return $this->question->penalty;
    }

    /**
     * Render colored string with specific hint value for given response using correct ending, returned by the matcher.
     *
     * Supposed to be called from render_hint() function of subclasses implementing hinted_string() and to_be_continued().
     */
    public function render_stringextension_hint($renderer, question_attempt $qa, question_display_options $options, $response) {
        $bestfit = $this->question->get_best_fit_answer($response);
        $matchresults = $bestfit['match'];

        if ($this->could_show_hint($matchresults, false)) {// Hint could be computed.
            if (!$matchresults->full) {// There is a hint to show.
                $wronghead = $renderer->render_unmatched($matchresults->match_heading());
                $correctpart = $this->render_raw_with_typos($renderer, $matchresults->approximatematch, $matchresults->approximatematch->str(), $matchresults->approximatematch->index_first(), $matchresults->approximatematch->length());
                $correctpart = $renderer->render_matched($correctpart, false);
                $hint = $renderer->render_hinted($this->hinted_string($matchresults));
                if ($this->to_be_continued($matchresults)) {
                    $hint .= $renderer->render_tobecontinued();
                }
                $wrongtail = '';
                if (core_text::strlen($hint) == 0) {
                    $wrongtail = $renderer->render_deleted($matchresults->tail_to_delete());
                }
                return $wronghead.$correctpart.$hint.$wrongtail;
            } else {// No hint, due to full match.
                return self::render_hint($renderer, $qa, $options, $response);
            }
        }
        return '';
    }

    /**
     * Implement in child classes to show hint.
     */
    public function hinted_string($matchresults) {
        return '';
    }

    /**
     * Implement in child classes to show to be continued after hint.
     */
    public function to_be_continued($matchresults) {
        return $matchresults->is_match() && !$matchresults->full &&
                $matchresults->index_first() + $matchresults->length() == core_text::strlen($matchresults->str()) &&
                $matchresults->length() !== qtype_preg_matching_results::NO_MATCH_FOUND;
    }

    /**
     * Render colored string showing matched and non-matched parts of response.
     */
    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        $bestfit = $this->question->get_best_fit_answer($response);
        $matchresults = $bestfit['match'];
        return $this->render_colored_string_by_matchresults($renderer, $matchresults);
    }

    /**
     * Actually renders the colored string.
     *
     * Placed outside render_hint to be able to get colored string without real question.
     * You still need a dummy one with 'engine' field set.
     *
     * @param $renderer a qtype_preg_renderer object to render parts of the string.
     * @param $matchresults matching results to show hint for.
     * @param $testing bool if true, called from testing - should render icon, also show string even for pure-assert match.
     */
    public function render_colored_string_by_matchresults($renderer, $matchresults, $testing = false) {

        $wronghead = '';
        $correctpart = '';
        $wrongtail = '';
        if ($testing) { // Add icon, showing whether match is full or no.
            $wronghead .= $renderer->render_match_icon($matchresults->full, $matchresults->typos->count());
        }

        $matchresults = $matchresults->approximatematch ? $matchresults->approximatematch : $matchresults;

        if ($this->could_show_hint($matchresults, $testing)) {
            $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), 0, $matchresults->index_first());
            $wronghead .= $renderer->render_unmatched($tmp, false);

            if (!isset($matchresults->length[-2]) || $matchresults->length[-2] == qtype_preg_matching_results::NO_MATCH_FOUND) {
                // No selection or no match with selection.
                $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), $matchresults->index_first(), $matchresults->length());
                $correctpart = $renderer->render_matched($tmp, false);
            } else {
                // We need to substract index_first of the match from all indexes when cuttring from matched part.
                $substract = $matchresults->index_first();
                // Before selection.
                $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), $substract, $matchresults->index_first(-2) - $substract);
                $correctpart = $renderer->render_matched($tmp, false);
                // Selection.
                $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), $matchresults->index_first(-2), $matchresults->length(-2));
                $tmp = $renderer->render_matched($tmp, false);
                $correctpart .= $renderer->render_selected($tmp, false);
                // After selection.
                $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), $matchresults->index_first(-2) + $matchresults->length(-2), $substract + $matchresults->length() - $matchresults->length(-2) - $matchresults->index_first(-2));
                $correctpart .=  $renderer->render_matched($tmp, false);
            }

            $tmp = $this->render_raw_with_typos($renderer, $matchresults, $matchresults->str(), $matchresults->index_first() + $matchresults->length(), core_text::strlen($matchresults->str()) - $matchresults->index_first() - $matchresults->length());
            $wrongtail = $renderer->render_unmatched($tmp, false);

            if ($this->to_be_continued($matchresults)) {
                $wrongtail .= $renderer->render_tobecontinued();
            }
        }

        return $wronghead.$correctpart.$wrongtail;
    }

    /**
     * Renders matched string part with typos.
     */
    protected function render_raw_with_typos($renderer, $matchresults, $str, $index_first, $length) {
        if ($matchresults->typos->count() === 0) {
            return htmlspecialchars(core_text::substr($str, $index_first, $length));
        }

        $result = '';
        $length = $index_first + $length;
        for ($i = $index_first; $i < $length; $i++) {
            switch (true) {
                case $matchresults->typos->contains_approximate(qtype_preg_typo::INSERTION, $i):
                    $result .= $renderer->render_typo('…');
                    break;
                case $matchresults->typos->contains_approximate(qtype_preg_typo::TRANSPOSITION, $i):
                case $matchresults->typos->contains_approximate(qtype_preg_typo::TRANSPOSITION, $i - 1):
                    $result .= $renderer->render_typo(core_text::substr($str, $i, 1));
                    break;
                case $matchresults->typos->contains_approximate(qtype_preg_typo::SUBSTITUTION, $i):
                    $result .= $renderer->render_unmatched(core_text::substr($str, $i, 1));
                    $result .= $renderer->render_typo('…');
                    break;
                case $matchresults->typos->contains_approximate(qtype_preg_typo::DELETION, $i):
                    $result .= $renderer->render_unmatched(core_text::substr($str, $i, 1));
                    break;
                default:
                    $result .= htmlspecialchars(core_text::substr($str, $i, 1));
            }
        }

        return $result;
    }

    public function could_show_hint($matchresults, $testing) {
        $queryengine = $this->question->get_query_matcher($this->question->engine);
        // Correctness should be shown if engine support partial matching or a full match is achieved.
        /* Also correctness should be shown if this is not pure-assert match as there is no green part on pure-assert matches;
         unless it's a testing with an icon, showing as that there is match without green part.*/
        return ($matchresults->is_match() || $queryengine->is_supporting(qtype_preg_matcher::PARTIAL_MATCHING)) && ($testing || $matchresults->length[0] !== 0);
    }

}

/**
 * Hint class for next character hint.
 */
class qtype_preg_hintnextchar extends qtype_preg_hintmatchingpart {

    //  Abstract hint class functions implementation.

    public function hint_response_based() {
        return false;// Could do without response to hint first character.
    }

    public function hint_description() {
        return get_string('hintnextchar', 'qtype_preg');
    }

    /**
     * Returns whether response allows for the hint to be done.
     */
    public function hint_available($response = null) {
        if ($response !== null) {
            $bestfit = $this->question->get_best_fit_answer($response);
            $matchresults = $bestfit['match'];
            return parent::hint_available($response) && $this->question->usecharhint && !$matchresults->full;
        } else {
            return $this->question->usecharhint;
        }
    }

    /**
     * Returns penalty for using specific hint of given hint type (possibly for given response).
     */
    public function penalty_for_specific_hint($response = null) {
            return $this->question->charhintpenalty;
    }

    //  qtype_preg_matching_hint functions implementation.
    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        return $this->render_stringextension_hint($renderer, $qa, $options, $response);
    }

    public function hinted_string($matchresults) {
        // One-character hint.
        $hintedstring = $matchresults->string_extension();
        if (core_text::strlen($hintedstring) > 0) {
            return core_text::substr($hintedstring, 0, 1);
        }
        return '';
    }

    public function to_be_continued($matchresults) {
        $hintedstring = $matchresults->string_extension();
        return core_text::strlen($hintedstring) > 1 || (is_object($matchresults->extendedmatch) && $matchresults->extendedmatch->full === false);
    }

}

/**
 * Hint class for next lexem hint.
 */
class qtype_preg_hintnextlexem extends qtype_preg_hintmatchingpart {

    // Cache values, filled by hinted_string function.
    protected $hinttoken;
    protected $endmatchindx;
    protected $inside;


    //  Abstract hint class functions implementation.

    public function hint_response_based() {
        return false;// Could do without response to hint first lexem.
    }

    public function hint_description() {
        $lexemname = $this->question->lexemusername;
        if ($lexemname == '') {
            $langobj = block_formal_langs::lang_object($this->question->langid);
            $lexemname = $langobj->lexem_name();
        }
        return get_string('hintnextlexem', 'qtype_preg', $lexemname);
    }

    /**
     * Returns whether response allows for the hint to be done.
     */
    public function hint_available($response = null) {
        if ($response !== null) {
            $bestfit = $this->question->get_best_fit_answer($response);
            $matchresults = $bestfit['match'];
            // TODO check that there is lexem after current situation.
            return parent::hint_available($response) && $this->question->uselexemhint && !$matchresults->full && is_object($matchresults->extendedmatch);
        } else {
            return $this->question->uselexemhint;
        }
    }

    /**
     * Returns penalty for using specific hint of given hint type (possibly for given response).
     */
    public function penalty_for_specific_hint($response = null) {
            return $this->question->lexemhintpenalty;
    }

    //  qtype_preg_matching_hint functions implementation.
    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        return $this->render_stringextension_hint($renderer, $qa, $options, $response);
    }

    public function hinted_string($matchresults) {
        //      Lexem hint.
        $langobj = block_formal_langs::lang_object($this->question->langid);
        $extendedmatch = $matchresults->extendedmatch;
        $endmatchindx = $matchresults->extensionstart;// Index of first non-matched character after match in extended match.
        $procstr = $langobj->create_from_string($extendedmatch->str());
        $stream = $procstr->stream;
        $tokens = $stream->tokens;

        if ($endmatchindx < 0) {// No match at all, but we still could give hint from the start of the string.
            $endmatchindx = 0;
        }

        //  Look for hint token.
        $hinttoken = null;
        $inside = false;// Whether match ended inside lexem.
        foreach ($tokens as $token) {
            if ($token->position()->colstart() >= $endmatchindx) {// Token starts after match ends.
                // Match ended between tokens, or we would have loop breaked already.
                $hinttoken = $token; // Next token hint, $inside == false.
                break;
            } else if ($token->position()->colend() >= $endmatchindx) {// Match ends inside this token.
                $hinttoken = $token;
                $inside = true;// Token completion hint.
                break;
            }
        }

        // Cache values.
        $this->hinttoken = $hinttoken;
        $this->inside = $inside;
        $this->endmatchindx = $endmatchindx;

        if ($hinttoken !== null) {// Found hint token.
            return core_text::substr($extendedmatch->str(), $endmatchindx, $hinttoken->position()->colend() - $endmatchindx + 1);
        } else {// There are some non-matched separators after end of last token. Just hint the end of generated string.
            return core_text::substr($extendedmatch->str(), $endmatchindx,  core_text::strlen($extendedmatch->str()) - $endmatchindx);
        }
    }

    public function to_be_continued($matchresults) {
        return  is_object($this->hinttoken) && // There is something to add to the response (sometimes we need to delete, not add).
                ( $this->hinttoken->position()->colend() + 1 < core_text::strlen($matchresults->extendedmatch->str()) || // Hinted token ends before generated string end.
                    $matchresults->extendedmatch->full === false ); // Generated string is not full.
    }
}

class qtype_preg_hinthowtofixpic extends qtype_poasquestion\hint {

    public function hint_type() {
        return qtype_poasquestion\hint::SINGLE_INSTANCE_HINT;
    }

    public function hint_description() {
        return get_string('hinthowtofixpic', 'qtype_preg');
    }

    // "Where" hint is obviously response based, since it used to better understand mistake message.
    public function hint_response_based() {
        return true;
    }

    /**
     * The hint is disabled when penalty is set above 1.
     */
    public function hint_available($response = null) {
        if ($response !== null && $this->question->usehowtofixpichint) {
            $bestfit = $this->question->get_best_fit_answer($response);
            $matchresults = $bestfit['match'];
            return $matchresults->typos->count() > 0;
        }
        return $this->question->usehowtofixpichint;
    }

    public function penalty_for_specific_hint($response = null) {
        if ($response !== null && $this->question->howtofixpichintpenalty) {
            $bestfit = $this->question->get_best_fit_answer($response);
            $matchresults = $bestfit['match'];
            return $matchresults->typos->count() > 0 ? $this->question->howtofixpichintpenalty : 0;
        }
        return $this->question->howtofixpichintpenalty;
    }

    public function render_hint($renderer, question_attempt $qa, question_display_options $options, $response = null) {
        // Get matching result.
        $matchingresult = clone $this->question->get_best_fit_answer($response)['match'];

        // Convert to lexem label format.
        list($string, $operations) = $matchingresult->typos->to_lexem_label_format();

        // crop string
        $startpos = $matchingresult->approximatematch->indexfirst[0];
        $length = $matchingresult->approximatematch->length[0];
        if ($startpos > -1 && $length > -1) {
            // we have already replaced all substitutions with deletion & insertion, so target match length should be increased with substitutions count
            $length += $matchingresult->typos->count(qtype_preg_typo::SUBSTITUTION);

            $string = \qtype_poasquestion\utf8_string::substr($string, $startpos, $length);
            $operations = array_slice($operations, $startpos, $length);
        }

        // Add '...' character.
        if (!$matchingresult->full) {
            $string = $string . core_text::code2utf8(0x2026);
            $operations []= 'normal';
        }

        $label = new \block_formal_langs_lexeme_label('');
        $label->set_text($string);
        $label->set_operations($operations);
        $label->recompute_size();

        $size = $label->get_size();
        $currentrect = (object)array(
                'width' => $size[0],
                'height' => $size[1],
                'x' => FRAME_SPACE,
                'y' => FRAME_SPACE
        );

        list($im, $palette) = \block_formal_langs_image_generator::create_default_image($size);
        $label->paint($im, $palette, $currentrect, true);

        // Output image.
        ob_start();
        imagepng($im);
        $imagebinary = ob_get_clean();
        imagedestroy($im);
        $imagetext  = 'data:image/png;base64,' . base64_encode($imagebinary);
        $imagesrc = html_writer::empty_tag('image', array('src' => $imagetext));
        return $imagesrc;
    }
}
