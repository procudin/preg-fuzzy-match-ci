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
 * Defines FA matcher class.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Valeriy Streltsov <vostreltsov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_poasquestion\utf8_string;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/preg_matcher.php');
require_once($CFG->dirroot . '/question/type/preg/preg_typo.php');
require_once($CFG->dirroot . '/question/type/preg/fa_matcher/fa_exec_state.php');
require_once($CFG->dirroot . '/blocks/formal_langs/block_formal_langs.php');

class qtype_preg_fa_matcher extends qtype_preg_matcher {

    // FA corresponding to the regex
    public $automaton = null;   // for testing purposes

    // Map of nested subpatterns:  (subpatt number => nested qtype_preg_node objects)
    protected $nestingmap = array();

    // States to backtrack to when generating extensions
    protected $backtrackstates = array();

    // Should we call bruteforce method to find a match?
    protected $bruteforcematch = false;

    // Should we call bruteforce method to generate a partial match extension?
    protected $bruteforcegeneration = false;

    // Max number of states during simulation
    protected $maxstatescount = 1000;

    // Max number of typos for current match
    protected $currentmaxtypos = 0;

    // Object of \block_formal_langs_abstract_language (cf. blocks/formal_langs for more details).
    protected $langobj = null;

    // Set of chars that cant be used as a typo.
    protected $typoblacklist = '';

    // Transpose pseudotransitions, array(fromstate => array(tostate => array(transitions))).
    protected $transposepseudotransitions = [];

    public function name() {
        return 'fa_matcher';
    }

    protected function get_engine_node_name($nodetype, $nodesubtype) {
        switch($nodetype) {
            case qtype_preg_node::TYPE_NODE_FINITE_QUANT:
            case qtype_preg_node::TYPE_NODE_INFINITE_QUANT:
            case qtype_preg_node::TYPE_NODE_CONCAT:
            case qtype_preg_node::TYPE_NODE_ALT:
            case qtype_preg_node::TYPE_NODE_ASSERT:
            case qtype_preg_node::TYPE_NODE_SUBEXPR:
            case qtype_preg_node::TYPE_NODE_COND_SUBEXPR:
                return 'qtype_preg_fa_' . $nodetype;
            case qtype_preg_node::TYPE_LEAF_CHARSET:
            case qtype_preg_node::TYPE_LEAF_META:
            case qtype_preg_node::TYPE_LEAF_ASSERT:
            case qtype_preg_node::TYPE_LEAF_COMPLEX_ASSERT:
            case qtype_preg_node::TYPE_LEAF_BACKREF:
            case qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL:
                return 'qtype_preg_fa_leaf';
        }

        return parent::get_engine_node_name($nodetype, $nodesubtype);
    }

    /**
     * Returns true for supported capabilities.
     * @param capability the capability in question.
     * @return bool is capanility supported.
     */
    public function is_supporting($capability) {
        switch($capability) {
            case qtype_preg_matcher::PARTIAL_MATCHING:
            case qtype_preg_matcher::CORRECT_ENDING:
            case qtype_preg_matcher::CHARACTERS_LEFT:
            case qtype_preg_matcher::SUBEXPRESSION_CAPTURING:
            case qtype_preg_matcher::CORRECT_ENDING_ALWAYS_FULL:
            case qtype_preg_matcher::FUZZY_MATCHING:
                return true;
            default:
                return false;
        }
    }

    /**
     * These subtypes do not have directly corresponding DST nodes.
     */
    protected function is_preg_node_acceptable($pregnode) {
        switch ($pregnode->type) {
            case qtype_preg_node::TYPE_LEAF_CHARSET:
            case qtype_preg_node::TYPE_LEAF_META:
            case qtype_preg_node::TYPE_LEAF_ASSERT:
            case qtype_preg_node::TYPE_LEAF_BACKREF:
            case qtype_preg_node::TYPE_LEAF_TEMPLATE:
            case qtype_preg_node::TYPE_NODE_TEMPLATE:
            case qtype_preg_node::TYPE_NODE_ERROR:
                return true;
            case qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL:
                // Equivalence checking doesn't support subexpression recursion for now.
                if ($this->get_options()->equivalencecheck && $pregnode->isrecursive) {
                    $str = '';
                    if ($pregnode->number == 0) { // Whole regex recursive call.
                        $str = get_string('description_leaf_subexpr_call_all_recursive', 'qtype_preg');
                    } else { // Particular subexpression recursive call.
                        $str = get_string('description_leaf_subexpr_call_recursive', 'qtype_preg', $pregnode->number);
                    }
                    return $str;
                } else {
                    return true;
                }
            default:
                return get_string($pregnode->type, 'qtype_preg');
        }
    }

    protected function create_fa_exec_stack_item($subexpr, $state, $startpos, $typos) {
        $stackitem = new qtype_preg_fa_stack_item();
        $stackitem->subexpr = $subexpr;
        $stackitem->recursionstartpos = $startpos;
        $stackitem->state = $state;
        $stackitem->full = false;
        $stackitem->next_char_flags = 0x00;
        $stackitem->matches = array();
        $stackitem->approximatematches = array();
        $stackitem->subexpr_to_subpatt = array(0 => $this->astroot);   // Remember this explicitly
        $stackitem->last_transition = null;
        $stackitem->last_match_len = 0;
        $stackitem->last_match_is_partial = false;
        $stackitem->typos = clone $typos;
        return $stackitem;
    }

    /**
     * Creates a processing state object for the given state filled with "nomatch" values.
     */
    protected function create_initial_state($state, $str, $startpos) {
        $result = new qtype_preg_fa_exec_state();
        $result->matcher = $this;
        $result->startpos = $startpos;
        $result->length = 0;
        $result->left = qtype_preg_matching_results::UNKNOWN_CHARACTERS_LEFT;
        $result->extendedmatch = null;
        $result->str = $str;
        $result->stack = array($this->create_fa_exec_stack_item(0, $state, $startpos, new qtype_preg_typo_container(clone $str)));
        $result->backtrack_states = array();
        if (in_array($state, $this->backtrackstates)) {
            $result->backtrack_states[] = $result;
        }
        return $result;
    }

    protected function set_last_transition($state, $transition, $length, $ispartial) {
        $state->set_last_transition($transition);
        $state->set_last_match_len($length);
        $state->set_last_match_is_partial($ispartial);
    }

    /**
     * Generates character(s) by the transition and returns new state.
     */
    protected function generate_char_by_transition($curstate, $transition, $str, $curpos) {
        $newstate = clone $curstate;
        $transitions = array_merge($transition->mergedbefore, array($transition), $transition->mergedafter);

        foreach ($transitions as $tr) {

            //echo "lookin at $tr\n";

            // One more crutch. Are there merged transitions?
            if (count($transitions) > 1 && $tr !== $transition) {
                //echo "inside if\n";
                $is_assert = $tr->pregleaf->type === qtype_preg_node::TYPE_LEAF_ASSERT;
                $affects_generation = $is_assert && ($tr->pregleaf->is_start_anchor() || $tr->pregleaf->is_end_anchor());

                // If this is is an anchor, everything is handled in the main transition.
                // Otherwise, we should call next_character here.
                if ($affects_generation) {
                    $this->after_transition_passed($newstate, $tr, $curpos, 0, false);
                    //echo "continue\n";
                    continue;
                }
            }

            list($flag, $newchr) = $tr->next_character($str, $newstate->str, $curpos, 0, $newstate);
            if ($flag === qtype_preg_leaf::NEXT_CHAR_CANNOT_GENERATE) {
                //echo "return null\n";
                return null;
            }
            //echo "generated: $flag '$newchr'\n";

            $length = 0;

            // TODO: ^ and \A

            if (is_object($newchr) && $newchr->length() > 0) {
                // If we had to end the generation, but generated something, cut out this path
                if ($newstate->is_flag_set(qtype_preg_leaf::NEXT_CHAR_END_HERE)) {
                    return null;
                }
                $newstate->str->concatenate($newchr);
                $length = $newchr->length();
                //var_dump($newstate->str->string());
                //var_dump($newchr->string());
                //echo "\n";
            }

            $this->after_transition_passed($newstate, $tr, $curpos, $length, false);
            $newstate->set_flag($flag);

            $curpos += $length;

            /*$recursionlevel = $newstate->recursion_level();
            echo "level $recursionlevel: generated char '$newchr' by $tr. length changed {$newstate->length} : {$newstate->length}\n";
            echo "new string is {$newstate->str}\n\n";*/
        }
        foreach ($transition->mergedafter as $tr) {
            $this->after_transition_passed($newstate, $tr, $curpos, 0, false);
        }
        $this->set_last_transition($newstate, $transition, $newstate->length - $curstate->length, false);
        return $newstate;
    }

    /**
     * Matches an array of transitions. If all transitions are matched, that means a full match. Partial match otherwise.
     */
    protected function match_transitions($curstate, $transitions, $str, $curpos, &$length, &$full, $addbacktracks, $consumeschar = true, $tryapproximate = false, $pseudotype = qtype_preg_typo::SUBSTITUTION) {
        $newstate = clone $curstate;
        $length = 0;
        $full = true;
        $approximateenabled = $tryapproximate && $this->currentmaxtypos > 0;

        foreach ($transitions as $tr) {
            $tmplength = 0;
            $ischartransition = $tr->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET;

            // We shouldn't match char transition for insertion pseudotransition.
            if (!$approximateenabled || !$ischartransition || $pseudotype != qtype_preg_typo::INSERTION) {
                $result =  $tr->pregleaf->match($str, $curpos, $tmplength, $newstate);
            } else {
                $result = false;
            }

            if ($result) {
                $this->after_transition_passed($newstate, $tr, $curpos, $tmplength, $addbacktracks);
                //echo "passed $tr\n";
            } else if ($approximateenabled) {
                // Try match pseudotransitions.
                if ($ischartransition && $newstate->typos()->count() < $this->currentmaxtypos) {
                    // Try match character pseudotransition.
                    $result = $this->match_character_pseudotransition($newstate, $tr, $str, $curpos, $tmplength, $addbacktracks, $pseudotype);
                } else if ($tr->pregleaf->type == qtype_preg_node::TYPE_LEAF_ASSERT) {
                    // Try match assert pseudotransition.
                    $result = $this->match_assert_pseudotransition($newstate, $tr, $str, $curpos, $addbacktracks, $consumeschar);
                }
            } else {
                $newstate->length += $tmplength;
            }

            // Increase curpos and length anyways, even if the match is partial (backrefs)
            $curpos += $tmplength;
            $length += $tmplength;

            if (!$result) {
                $full = false;
                break;
            }

            // Unbelievable crutch: we should stop matching merged transitions that
            // could be placed outside this subexpression in the original automaton
            if ($newstate->recursion_level() > 0 && $newstate->is_subexpr_captured_top($newstate->subexpr())) {
                break;
            }
        }

        if (!$full) {
            $newstate->set_state($curstate->state());
            $newstate->set_full(false);
            $newstate->left = qtype_preg_matching_results::UNKNOWN_CHARACTERS_LEFT;
        }

        return $newstate;
    }

    protected function match_character_pseudotransition($curstate, $transition, $str, &$curpos, &$length, $addbacktracks, $pseudotype) {
        $result = false;
        $length = 0;
        $issub = $pseudotype == qtype_preg_typo::SUBSTITUTION;

        // Do nothing if it's substitution and current char is in language blacklist
        if ($issub && $this->is_char_in_lang_typo_blacklist($str[$curpos])) {
            return $result;
        }

        // Do substitution if only $curpos is inside of string.
        if ($issub && $curpos >= $str->length()) {
            return $result;
        }

        // Try to generate transition character.
        list($flag, $char) = $transition->next_character($str, $str, $curpos, 0, $curstate, $this->typoblacklist);
        if ($flag != qtype_preg_leaf::NEXT_CHAR_CANNOT_GENERATE) {
            // If successfull generated.
            $result = true;
            $curstate->typos()->add(new qtype_preg_typo($pseudotype, $curpos, $char));
            $length = $issub ? 1 : 0;
            $this->after_transition_passed($curstate, $transition, $curpos, $length, $addbacktracks, !$issub);
        }

        return $result;
    }

    protected function match_assert_pseudotransition($curstate, $transition, $str, &$curpos, $addbacktracks, $ismerged) {
        $result = false;
        $errorscount = $curstate->typos()->count();
        $strlen = $str->length();
        $subtype = $transition->pregleaf->subtype;
        switch ($subtype) {
            case qtype_preg_leaf_assert::SUBTYPE_DOLLAR:
                if ($curpos == $strlen - 1) {
                    // If it's last char - delete it.
                    $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::DELETION, $curpos));
                    $result = true;
                    $curstate->length = $curpos;
                    $curstate->startpos = 0;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                } else if (!$ismerged) {
                    // If unmerged, generate \n insertion.
                    $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::INSERTION, $curpos, new utf8_string("\n")));
                    $result = true;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                } else {
                    // If merged, let char transition generate \n insertion.
                    $result = true;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                }
                break;
            case qtype_preg_leaf_assert::SUBTYPE_CIRCUMFLEX:
                if ($curpos == 1) {
                    // If it's second char - delete it.
                    $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::DELETION, 0));
                    $result = true;
                    $curstate->length = $curpos;
                    $curstate->startpos = 0;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                } else if (!$ismerged) {
                    // If unmerged, generate \n insertion.
                    $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::INSERTION, $curpos, new utf8_string("\n")));
                    $result = true;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                } else {
                    // If merged, let char transition generate \n insertion.
                    $result = true;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                }
                break;
            case qtype_preg_leaf_assert::SUBTYPE_ESC_A:
                // Delete all characters before curpos.
                if ($errorscount + $curpos <= $this->currentmaxtypos) {
                    for ($pos = 0; $pos < $curpos; $pos++) {
                        $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::DELETION, $pos));
                    }
                    $result = true;
                    $curstate->length = $curpos;
                    $curstate->startpos = 0;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                }
                break;
            case qtype_preg_leaf_assert::SUBTYPE_CAPITAL_ESC_Z:
                // If merged and it's last string character, let char transition generate \n insertion.
                if ($ismerged && $curpos == $strlen - 1) {
                    $result = true;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                    break;
                }
                // Correct missing break statement, we should check \Z same as \z.
            case qtype_preg_leaf_assert::SUBTYPE_SMALL_ESC_Z:
                // Delete all characters after curpos(including curpos).
                if ($errorscount + $strlen - $curpos <= $this->currentmaxtypos) {
                    for ($pos = $curpos; $pos < $strlen; $pos++) {
                        $curstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::DELETION, $pos));
                    }
                    $result = true;
                    $curstate->length += $strlen - $curpos;
                    $curpos += $strlen - $curpos;
                    $this->after_transition_passed($curstate, $transition, $curpos, 0, $addbacktracks);
                }
                break;
        }
        return $result;
    }

    protected function match_deletion_pseudotransitions($curstate, $curpos) {
        // Don't try deletion for initial or end state
        //$subpatt = $newstate->matcher->get_ast_root()->subpattern;
        if (!isset($curstate->stack[0]->matches[0]) || $curstate->is_full()) {
            return null;
        }

        // Do nothing if current char is in language blacklist
        if ($this->is_char_in_lang_typo_blacklist($curstate->str[$curpos])) {
            return null;
        }

        $newstate = clone $curstate;
        $newstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::DELETION, $curpos));
        $newstate->length += 1;

        return $newstate;
    }


    protected function match_recursive_transition_begin($curstate, $transition, $str, $curpos, &$length, &$full, $addbacktracks, $tryapproximate = false, $pseudotype = qtype_preg_typo::SUBSTITUTION) {
        $result = $this->match_transitions($curstate, $transition->mergedbefore, $str, $curpos, $length, $full, $addbacktracks, $tryapproximate, $pseudotype);
        if ($full) {
            $this->set_last_transition($result, $transition, $length, false);
        }
        return $result;
    }

    protected function match_recursive_transition_end($newstate, $recursionstartpos, $recursionlength, $str, $curpos, &$length, &$full, $addbacktracks, $tryapproximate = false, $pseudotype = qtype_preg_typo::SUBSTITUTION) {
        $this->after_transition_passed($newstate, $newstate->last_transition(), $recursionstartpos, $recursionlength, $addbacktracks);
        $newstate->length -= $recursionlength;
        return $this->match_transitions($newstate, $newstate->last_transition()->mergedafter, $str, $curpos, $length, $full, $addbacktracks, $tryapproximate, $pseudotype);
    }

    /**
     * Checks if this transition (with all merged to it) matches a character. Returns a new state.
     */
    protected function match_regular_transition($curstate, $transition, $str, $curpos, &$length, &$full, $addbacktracks, $tryapproximate = false, $pseudotype = qtype_preg_typo::SUBSTITUTION) {
        $transitions = array_merge($transition->mergedbefore, array($transition), $transition->mergedafter);
        $newstate = $this->match_transitions($curstate, $transitions, $str, $curpos, $length, $full, $addbacktracks, $transition->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET, $tryapproximate, $pseudotype);
        $this->set_last_transition($newstate, $transition, $newstate->length - $curstate->length, !$full);
        return $newstate;
    }

    /**
     * Updates all fields in the newstate after a transition match.
     */
    protected function after_transition_passed($newstate, $transition, $curpos, $length, $addbacktracks = true, $afterinsertion = false) {
        $endstates = $this->automaton->get_end_states($newstate->subexpr());

        $newstate->set_state($transition->to);
        $newstate->set_full(in_array($newstate->state(), $endstates));
        $newstate->left = $newstate->is_full() ? 0 : qtype_preg_matching_results::UNKNOWN_CHARACTERS_LEFT;
        $newstate->length += $length;
        $newstate->write_tag_values($transition, $curpos, $length, $afterinsertion);

        if ($addbacktracks && in_array($transition->to, $this->backtrackstates)) {
            $newstate->backtrack_states[] = $newstate;
        }
    }

    /**
     * Returns an array of states which can be reached without consuming characters.
     * @param qtype_preg_fa_exec_state startstates states to go from.
     * @return an array of states (including the start state) which can be reached without consuming characters.
     */
    protected function epsilon_closure($startstates, $str, $addbacktracks) {
        $curstates = $startstates;
        $approximateenabled = $this->currentmaxtypos > 0;
        $result = array(\qtype_preg\fa\transition::GREED_LAZY => array(),
                        \qtype_preg\fa\transition::GREED_GREEDY => $startstates
                        );
        while (!empty($curstates)) {
            // Get the current state and iterate over all transitions.
            $curstate = array_pop($curstates);
            $curpos = $curstate->startpos + $curstate->length;
            $transitions = $this->automaton->get_adjacent_transitions($curstate->state(), true);
            foreach ($transitions as $transition) {
                $length = 0;
                $full = true;
                $isinsertion = $approximateenabled && $transition->pregleaf->type == qtype_preg_node::TYPE_LEAF_CHARSET;

                $empty = $transition->pregleaf->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY
                        || $approximateenabled && $transition->pregleaf->type == qtype_preg_leaf::TYPE_LEAF_ASSERT && ($transition->pregleaf->is_start_anchor() || $transition->pregleaf->is_end_anchor());

                // Try match empty or insertion transition
                if ($isinsertion || $empty) {
                    $newstate = $this->match_regular_transition($curstate, $transition, $str, $curpos, $length, $full, $addbacktracks, true, qtype_preg_typo::INSERTION);
                } else {
                    continue;
                }

                if (!$full) {
                    continue;
                }

                // This could be the end of a recursive call.
                while ($full && $newstate->recursion_level() > 0 && $newstate->is_full()) {
                    $topitem = array_pop($newstate->stack);
                    $recursionmatch = $topitem->last_subexpr_match($this->get_options()->mode, $topitem->subexpr);
                    $newstate = $this->match_recursive_transition_end($newstate, $topitem->recursionstartpos, $recursionmatch[1], $str, $curpos, $length, $full, $addbacktracks, true, qtype_preg_typo::INSERTION);
                }

                if (!$full) {
                    continue;
                }

                // Resolve ambiguities if any.
                $number = $newstate->state();
                $key = $transition->greediness == \qtype_preg\fa\transition::GREED_LAZY
                     ? \qtype_preg\fa\transition::GREED_LAZY
                     : \qtype_preg\fa\transition::GREED_GREEDY;
                if (!isset($result[$key][$number]) || $newstate->leftmost_longest($result[$key][$number])) {
                    $result[$key][$number] = $newstate;
                    if ($key != \qtype_preg\fa\transition::GREED_LAZY && !($isinsertion && $transition->from === $transition->to)) {
                        $curstates[] = $newstate;
                    }
                }
            }
        }
        return $result;
    }

    protected function get_resume_state($str, $laststate) {

        if ($laststate->last_match_is_partial()) {
            $resumestate = clone $laststate;
            $resumestate->length -= $laststate->last_match_len();
            $resumestate->str = $resumestate->str->substring(0, $resumestate->startpos + $resumestate->length);
            return $resumestate;
        }

        $endstates = $this->automaton->get_end_states($laststate->subexpr());

        // There was no match at all, or the last transition was fully-matched.
        $curpos = $laststate->startpos + $laststate->length;

        // Check for a \Z \z or $ assertion before the eps-closure of the end state. Then it's possible to remove few characters.
        $transitions = $this->automaton->get_adjacent_transitions($laststate->state(), true);
        foreach ($transitions as $transition) {
            if ($transition->loopsback || !($transition->pregleaf->type == qtype_preg_node::TYPE_LEAF_ASSERT && $transition->pregleaf->is_end_anchor())) {
                continue;
            }
            $closure = $this->epsilon_closure(array($laststate->state() => $laststate), $str, false);
            $closure = array_merge($closure[\qtype_preg\fa\transition::GREED_LAZY], $closure[\qtype_preg\fa\transition::GREED_GREEDY]);
            foreach ($closure as $curclosure) {
                if (in_array($curclosure->state(), $endstates)) {
                    // The end state is reachable; return it immediately.
                    $result = clone $laststate;
                    $this->after_transition_passed($result, $transition, $curpos, 0, false);
                    return $result;
                }
            }
        }

        // Just return the state as is.
        return $laststate;
    }

    /**
     * Returns the minimal path to complete a partial match.
     * @param qtype_poasquestion\utf8_string str - original string that was matched.
     * @param qtype_preg_fa_exec_state laststate - the last state matched.
     * @return object of qtype_preg_fa_exec_state.
     */
    protected function generate_extension_brute_force($str, $laststate) {
        $endstates = $this->automaton->get_end_states($laststate->subexpr());
        $resumestate = $this->get_resume_state($str, $laststate);
        if (in_array($resumestate->state(), $endstates)) {
            return $resumestate;
        }

        $curstates = array($resumestate);
        $result = null;

        $statescount = count($curstates);

        while (!empty($curstates)) {
            $curstate = array_pop($curstates);
            --$statescount;
            $curpos = $curstate->startpos + $curstate->length;
            if ($curstate->is_full() && ($result === null || $curstate->leftmost_shortest($result))) {
                $result = $curstate;
            }
            $transitions = $this->automaton->get_adjacent_transitions($curstate->state(), true);
            foreach ($transitions as $transition) {
                if ($transition->pregleaf->type === qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL) {
                    continue;
                }
                // Skip loops.
                if ($transition->loopsback) {
                    continue;
                }

                // Create a new state.
                $newstate = $this->generate_char_by_transition($curstate, $transition, $str, $curpos);
                if ($newstate === null) {
                    continue;
                }
                // Is it longer than existing one?
                if ($result !== null && $newstate->length > $result->length) {
                    continue;
                }

                // This could be the end of a recursive call.
                $length = $transition->pregleaf->consumes($curstate);
                $full = true;
                while ($full && $newstate->recursion_level() > 0 && $newstate->is_full()) {
                    $topitem = array_pop($newstate->stack);
                    $recursionmatch = $topitem->last_subexpr_match($this->get_options()->mode, $topitem->subexpr);
                    $newstate = $this->match_recursive_transition_end($newstate, $topitem->recursionstartpos, $recursionmatch[1], $str, $curpos, $length, $full, false);
                }

                if (!$full) {
                    continue;
                }

                // Save the new state.
                if ($statescount >= $this->maxstatescount) {
                    break;
                }
                $curstates[] = $newstate;
                ++$statescount;
            }
        }
        return $result;
    }

    /**
     * Returns the minimal path to complete a partial match.
     * @param qtype_poasquestion\utf8_string str - original string that was matched.
     * @param qtype_preg_fa_exec_state laststate - the last state matched.
     * @return object of qtype_preg_fa_exec_state.
     */
    protected function generate_extension_fast($str, $laststate) {
        $endstates = $this->automaton->get_end_states($laststate->subexpr());
        $resumestate = $this->get_resume_state($str, $laststate);
        if (in_array($resumestate->state(), $endstates)) {
            return $resumestate;
        }

        $states = array();
        $curstates = array();

        // Create an array of processing states for all fa states (the only resumestate, other states are null yet).
        foreach ($this->automaton->get_states() as $curstate) {
            $states[$curstate] = $curstate === $resumestate->state()
                               ? $resumestate
                               : null;
        }

        // Get an epsilon-closure of the resume state.
        $closure = $this->epsilon_closure(array($resumestate->state() => $resumestate), $str, false);
        $closure = array_merge($closure[\qtype_preg\fa\transition::GREED_LAZY], $closure[\qtype_preg\fa\transition::GREED_GREEDY]);
        foreach ($closure as $curclosure) {
            $states[$curclosure->state()] = $curclosure;
            $curstates[] = $curclosure->state();
        }

        $result = null;

        $statescount = count($curstates);

        // Do search.
        while (!empty($curstates)) {
            $reached = array();
            // We'll replace curstates with reached by the end of this loop.
            while (!empty($curstates)) {
                // Get the current state and iterate over all transitions.
                $curstate = $states[array_pop($curstates)];
                --$statescount;
                $curpos = $curstate->startpos + $curstate->length;
                if ($curstate->is_full() && ($result === null || $curstate->leftmost_shortest($result))) {
                    $result = $curstate;
                }
                $transitions = $this->automaton->get_adjacent_transitions($curstate->state(), true);
                foreach ($transitions as $transition) {
                    if ($transition->pregleaf->subtype === qtype_preg_leaf_meta::SUBTYPE_EMPTY) {
                        continue;
                    }
                    if ($transition->pregleaf->type === qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL) {
                        continue;
                    }
                    // Skip loops.
                    if ($transition->loopsback) {
                        continue;
                    }

                    // Create a new state.
                    $newstate = $this->generate_char_by_transition($curstate, $transition, $str, $curpos);
                    if ($newstate === null) {
                        continue;
                    }
                    // Is it longer than existing one?
                    if ($result !== null && $newstate->length > $result->length) {
                        continue;
                    }

                    // This could be the end of a recursive call.
                    $length = $transition->pregleaf->consumes($curstate);
                    $full = true;
                    while ($full && $newstate->recursion_level() > 0 && $newstate->is_full()) {
                        $topitem = array_pop($newstate->stack);
                        $recursionmatch = $topitem->last_subexpr_match($this->get_options()->mode, $topitem->subexpr);
                        $newstate = $this->match_recursive_transition_end($newstate, $topitem->recursionstartpos, $recursionmatch[1], $str, $curpos, $length, $full, false);
                    }

                    if (!$full) {
                        continue;
                    }

                    // Save the current result.
                    $number = $newstate->state();
                    if (!isset($reached[$number]) || $newstate->leftmost_shortest($reached[$number])) {
                        $reached[$number] = $newstate;
                    }
                }
            }

            $reached = $this->epsilon_closure($reached, $str, false);
            $reached = array_merge($reached[\qtype_preg\fa\transition::GREED_LAZY], $reached[\qtype_preg\fa\transition::GREED_GREEDY]);

            // Replace curstates with reached.
            foreach ($reached as $curstate) {
                // Currently stored state needs replacement if it's null, or if it's worse than the new state.
                if ($states[$curstate->state()] === null || $curstate->leftmost_shortest($states[$curstate->state()])) {
                    if ($statescount >= $this->maxstatescount) {
                        break;
                    }
                    $states[$curstate->state()] = $curstate;
                    $curstates[] = $curstate->state();
                    ++$statescount;
                }
            }
        }
        return $result;
    }

    /**
     * This method should be used if there are backreferences in the regex.
     * Returns array of all possible matches.
     */
    protected function match_brute_force($str, $startpos) {
        $maxrecursionlevel = $str->length() + 1;

        $fullmatches = array();       // Possible full matches.
        $partialmatches = array();    // Possible partial matches.

        $curstates = array();    // States which the automaton is in at the current wave front.
        $lazystates = array();   // States reached lazily.

        foreach ($this->automaton->get_start_states(0) as $state) {
            $curstates[] = $this->create_initial_state($state, $str, $startpos);
        }

        $statescount = count($curstates);

        // Do search.
        while (!empty($curstates)) {
            $reached = array();
            while (!empty($curstates)) {
                // Get the current state and iterate over all transitions.
                $curstate = array_pop($curstates);
                --$statescount;
                $curpos = $startpos + $curstate->length;
                $cursubexpr = $curstate->subexpr();
                $recursionlevel = $curstate->recursion_level();
                $transitions = $this->automaton->get_adjacent_transitions($curstate->state(), true);

                //echo "\n";

                foreach ($transitions as $transition) {
                    $length = 0;
                    $full = true;

                    //$char = core_text::substr($str, $curpos, 1);
                    //echo "level $recursionlevel: trying $transition at pos $curpos (char '$char')\n";

                    if ($transition->pregleaf->type === qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL && $recursionlevel < $maxrecursionlevel) {
                        // Handle a recursive transition
                        $newstate = $this->match_recursive_transition_begin($curstate, $transition, $str, $curpos, $length, $full, true);
                        if ($full) {
                            $startstates = $this->automaton->get_start_states($transition->pregleaf->number);
                            foreach ($startstates as $state) {
                                $newnewstate = clone $newstate;
                                $newnewstate->stack[] = $this->create_fa_exec_stack_item($transition->pregleaf->number, $state, $curpos, $newstate->typos());
                                //echo "add recursive state {$newnewstate->state()}\n";
                                $reached[] = $newnewstate;
                            }
                        }
                    } else if ($transition->pregleaf->type !== qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL) {
                        // Handle a non-recursive transition transition
                        $newstate = $this->match_regular_transition($curstate, $transition, $str, $curpos, $length, $full, true);

                        if ($full) {

                            //echo "level $recursionlevel: MATCHED $transition at pos $curpos (char '$char'). length changed {$curstate->length} : {$newstate->length}\n";
                            //echo $newstate->subpatts_to_string();

                            // Filter out states that did not start the actual subexpression call.
                            $skip = !$newstate->is_subexpr_match_started($cursubexpr);

                            // Filter out states that have senseless zero-length loops.
                            $skip = $skip || ($transition->loopsback && $newstate->has_null_iterations());

                            // This could be the end of a recursive call.
                            while (!$skip && $full && $newstate->recursion_level() > 0 && $newstate->is_full()) {
                                $topitem = array_pop($newstate->stack);
                                $recursionmatch = $topitem->last_subexpr_match($this->get_options()->mode, $topitem->subexpr);
                                $newstate = $this->match_recursive_transition_end($newstate, $topitem->recursionstartpos, $recursionmatch[1], $str, $curpos, $length, $full, true);
                            }

                            $skip = $skip || !$full;

                            // Save the current match.
                            if (!$skip) {
                                if ($transition->greediness == \qtype_preg\fa\transition::GREED_LAZY) {
                                    $lazystates[] = $newstate;
                                } else {
                                    //echo "add state {$newstate->state()}\n";
                                    $reached[] = $newstate;
                                }
                            }

                            if (!$skip && $newstate->recursion_level() === 0 && $newstate->is_full()) {
                                $fullmatches[] = $newstate;
                            }
                        }
                    }

                    if (!$full && empty($fullmatches)) {
                        // Handle a partial match.
                        //echo "level $recursionlevel: not matched, partial match length is $length\n";
                        $partialmatches[] = $newstate;
                    }
                }

                // If there's no full match yet and no states reached, try the lazy ones.
                if (empty($fullmatches) && empty($reached) && !empty($lazystates)) {
                    $reached[] = array_pop($lazystates);
                }
            }
            foreach ($reached as $newstate) {
                if ($statescount >= $this->maxstatescount) {
                    break;
                }
                $curstates[] = $newstate;
                ++$statescount;
            }
        }

        // Return array of all possible matches.
        $result = $fullmatches;
        if (empty($result)) {
            $result = $partialmatches;
        }
        return $result;
    }

    /**
     * Creates an index object for fast matching. In classical TNFS we would use one dimension (state number),
     * but to support recursion we have to add one more dimension. In fact, $recursionlevel is a string
     * sequence containing numbers of subsequently called subexpressions.
     */
    protected static function create_index($recursionlevel, $statenumber) {
        $result = new stdClass();
        $result->recursionlevel = $recursionlevel;
        $result->state = $statenumber;
        return $result;
    }

    protected static function ensure_index_exists(&$array, $key0, $key1, $defaultvalue) {
        if (!isset($array[$key0])) {
            $array[$key0] = array();
        }
        if (!isset($array[$key0][$key1])) {
            $array[$key0][$key1] = $defaultvalue;
        }
    }

    /**
     * This method should be used if there are no backreferences in the regex.
     * Returns array of all possible matches.
     */
    protected function match_fast($str, $startpos) {
        $maxrecursionlevel = $str->length() + 1;

        $states = array('0' => array()); // Objects of qtype_preg_fa_exec_state. First dimension is recursion level, second is state number.
        $curstates = array();          // Indexes of states which the automaton is in at the current wave front. Use stdClass with "recursionlevel" and "state" fields.
        $lazystates = array();         // States (objects!) reached lazily.
        $partialmatches = array();     // Possible partial matches.

        $startstates = $this->automaton->get_start_states(0);

        $endstatereached = false;

        // Create an array of processing states for all fa states (the only initial state, other states are null yet).
        foreach ($this->automaton->get_states() as $curstate) {
            $states['0'][$curstate] = in_array($curstate, $startstates)
                                  ? $this->create_initial_state($curstate, $str, $startpos)
                                  : null;
        }

        $reached = array();

        // Get an epsilon-closure of the initial state.
        foreach ($states['0'] as $state) {
            if ($state !== null) {
                $reached[] = $state;
            }
        }

        $closure = $this->epsilon_closure($reached, $str, true);
        $lazystates = $closure[\qtype_preg\fa\transition::GREED_LAZY];
        $closure = $closure[\qtype_preg\fa\transition::GREED_GREEDY];
        $compareonnextstep = [];

        foreach ($closure as $state) {
            $states['0'][$state->state()] = $state;
            $curstates[] = self::create_index('0', $state->state());
            $endstatereached = $endstatereached || $state->is_full();
        }

        $statescount = count($curstates);

        // Do search.
        while (!empty($curstates)) {
            $reached = $compareonnextstep; // $reached uses stdClass with "recursionlevel" and "state" fields as well
            $compareonnextstep = array();
            // We'll replace curstates with reached by the end of this loop.
            while (!empty($curstates)) {
                // Get the current state and iterate over all transitions.
                $index = array_pop($curstates);
                $from = $index->state;
                $curstate = $states[$index->recursionlevel][$index->state];
                --$statescount;
                $curpos = $curstate->startpos + $curstate->length;
                $cursubexpr = $curstate->subexpr();
                $curerrcount = $curstate->typos()->count();
                $recursionlevel = $curstate->recursion_level();
                $transitions = $this->automaton->get_adjacent_transitions($curstate->state(), true);

                //echo "\n";

                foreach ($transitions as $transition) {
                    if ($transition->pregleaf->subtype == qtype_preg_leaf_meta::SUBTYPE_EMPTY
                            || $this->currentmaxtypos > 0 && $transition->pregleaf->type == qtype_preg_leaf::TYPE_LEAF_ASSERT && ($transition->pregleaf->is_start_anchor() || $transition->pregleaf->is_end_anchor())) {
                        continue;
                    }

                    $length = 0;
                    $full = true;

                    //$char = core_text::substr($str, $curpos, 1);
                    //echo "level $recursionlevel: trying $transition at pos $curpos (char '$char')\n";

                    if ($transition->pregleaf->type === qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL && $recursionlevel < $maxrecursionlevel) {
                        // Handle a recursive transition
                        $newstate = $this->match_recursive_transition_begin($curstate, $transition, $str, $curpos, $length, $full, true, true, qtype_preg_typo::SUBSTITUTION);
                        if ($full) {
                            $startstates = $this->automaton->get_start_states($transition->pregleaf->number);
                            foreach ($startstates as $state) {
                                $newnewstate = clone $newstate;
                                $newnewstate->stack[] = $this->create_fa_exec_stack_item($transition->pregleaf->number, $state, $curpos, $newnewstate->typos());
                                $index = self::create_index($newnewstate->recursive_calls_sequence(), $newnewstate->state());
                                self::ensure_index_exists($reached, $index->recursionlevel, $index->state, null);
                                if ($reached[$index->recursionlevel][$index->state] === null || $newnewstate->leftmost_longest($reached[$index->recursionlevel][$index->state])) {
                                    //echo "add recursive state {$newnewstate->state()} for subexpr {$transition->pregleaf->number}\n";
                                    $reached[$index->recursionlevel][$index->state] = $newnewstate;
                                }
                            }
                        }
                    } else if ($transition->pregleaf->type !== qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL) {
                        // Handle a non-recursive transition transition
                        $newstate = $this->match_regular_transition($curstate, $transition, $str, $curpos, $length, $full, true, true, qtype_preg_typo::SUBSTITUTION);

                        if ($full) {

                            //echo "level $recursionlevel: MATCHED $transition at pos $curpos (char '$char'). length changed {$curstate->length} : {$newstate->length}\n";
                            //echo $newstate->subpatts_to_string();

                            // Filter out states that did not start the actual subexpression call.
                            $skip = !$newstate->is_subexpr_match_started($cursubexpr);

                            $endstatereached = $endstatereached || (!$skip && $newstate->recursion_level() === 0 && $newstate->is_full());

                            // This could be the end of a recursive call.
                            while (!$skip && $full && $newstate->recursion_level() > 0 && $newstate->is_full()) {
                                $topitem = array_pop($newstate->stack);
                                $recursionmatch = $topitem->last_subexpr_match($this->get_options()->mode, $topitem->subexpr);
                                $newstate = $this->match_recursive_transition_end($newstate, $topitem->recursionstartpos, $recursionmatch[1], $str, $curpos, $length, $full, true, true, qtype_preg_typo::SUBSTITUTION);
                                //$newtopitem = end($newstate->stack);
                                //echo "ended matching of {$topitem->subexpr}, now matching {$newtopitem->subexpr} from state {$newstate->state()}\n";
                            }

                            $skip = $skip || !$full;

                            // Save the current result.
                            if (!$skip) {
                                if ($transition->greediness == \qtype_preg\fa\transition::GREED_LAZY) {
                                    $lazystates[] = $newstate;
                                } else {
                                    $index = self::create_index($newstate->recursive_calls_sequence(), $newstate->state());
                                    self::ensure_index_exists($reached, $index->recursionlevel, $index->state, null);
                                    if ($reached[$index->recursionlevel][$index->state] === null || $newstate->leftmost_longest($reached[$index->recursionlevel][$index->state])) {
                                        //echo "add state {$newstate->state()}\n";
                                        $reached[$index->recursionlevel][$index->state] = $newstate;
                                    }
                                }
                            }
                        }
                    }

                    if (!$full && !$endstatereached) {
                        // Handle a partial match. Partial match shouldn't contain any typos at the end of the match.
                        $pos = $newstate->startpos + $newstate->length;
                        if (!$newstate->typos()->contains(qtype_preg_typo::INSERTION, $pos)
                            && !$newstate->typos()->contains(qtype_preg_typo::SUBSTITUTION, $pos - 1)
                            && !$newstate->typos()->contains(qtype_preg_typo::DELETION, $pos - 1)) {
                            $partialmatches[] = $newstate;
                        }
                    } elseif ($full && !$endstatereached
                        && $newstate->typos()->count(qtype_preg_typo::SUBSTITUTION) > $curstate->typos()->count(qtype_preg_typo::SUBSTITUTION)) {
                        // Handle a partial match when last state consumed char with substitution pseudo-transition.
                        $pos = $curstate->startpos + $curstate->length;
                        if (!$curstate->typos()->contains(qtype_preg_typo::INSERTION, $pos)
                            && !$curstate->typos()->contains(qtype_preg_typo::SUBSTITUTION, $pos - 1)
                            && !$curstate->typos()->contains(qtype_preg_typo::DELETION, $pos - 1)) {
                            $partialmatches[] = clone $curstate;
                        }
                    }
                }

                // Try transpose pseudotransitions.
                if ($this->options->mergeassertions
                    && $curerrcount < $this->currentmaxtypos
                    && $curpos < $str->length() - 1
                    && !$this->is_char_in_lang_typo_blacklist($str[$curpos])
                    && !$this->is_char_in_lang_typo_blacklist($str[$curpos + 1])) {
                    $tmp1 = $str[$curpos];
                    $tmp2 = $str[$curpos + 1];
                    if (strcmp($tmp1, $tmp2) !== 0) {
                        $str[$curpos + 1] = $tmp1;
                        $str[$curpos] = $tmp2;
                        foreach ($this->transposepseudotransitions[$from] as $to => $transitions) {
                            if (isset($reached[$recursionlevel][$to]) && $reached[$recursionlevel][$to]->typos()->count() < $curerrcount) {
                                continue;
                            }

                            foreach ($transitions as $tr) {
                                $newstate = $this->match_transitions($curstate, $tr, $str, $curpos, $length, $full, true, true, false);
                                if ($full) {
                                    $newstate->typos()->add(new qtype_preg_typo(qtype_preg_typo::TRANSPOSITION, $curpos));
                                    $index = self::create_index($newstate->recursive_calls_sequence(), $newstate->state());
                                    self::ensure_index_exists($compareonnextstep, $index->recursionlevel, $index->state, null);
                                    if ($compareonnextstep[$index->recursionlevel][$index->state] === null || $newstate->leftmost_longest($compareonnextstep[$index->recursionlevel][$index->state])) {
                                        $compareonnextstep[$index->recursionlevel][$index->state] = $newstate;
                                    }
                                }
                            }
                        }
                        $str[$curpos + 1] = $tmp2;
                        $str[$curpos] = $tmp1;
                    }
                }

                // Try to deletion pseudotransition.
                if ($curerrcount < $this->currentmaxtypos) {
                    $newstate = $this->match_deletion_pseudotransitions($curstate, $curpos);
                    if ($newstate !== null) {
                        self::ensure_index_exists($reached, $recursionlevel, $from, null);
                        if ($reached[$recursionlevel][$from] === null || $newstate->leftmost_longest($reached[$recursionlevel][$from])) {
                            $reached[$recursionlevel][$from] = $newstate;
                        }
                    }
                }
            }

            // If there's no full match yet and no states reached, try the lazy ones.
            if (!$endstatereached && empty($reached) && !empty($lazystates)) {
                $lazy = array_pop($lazystates);
                $index = self::create_index($lazy->recursive_calls_sequence(), $lazy->state());
                self::ensure_index_exists($reached, $index->recursionlevel, $index->state, null);
                $reached[$index->recursionlevel][$index->state] = $lazy;
            }

            // Iterate over reached states. Get epsilon-closure for each recursion level.
            foreach ($reached as $recursionlevel => $reachedforlevel) {
                $reached[$recursionlevel] = $this->epsilon_closure($reachedforlevel, $str, true);
                $lazystates = array_merge($lazystates, $reached[$recursionlevel][\qtype_preg\fa\transition::GREED_LAZY]);
                $reached[$recursionlevel] = $reached[$recursionlevel][\qtype_preg\fa\transition::GREED_GREEDY];
            }

            foreach ($reached as $newstates) {
                foreach ($newstates as $newstate) {
                    $errorscount = $newstate->typos()->count();
                    if ($errorscount > $this->currentmaxtypos) {
                       continue;
                    }

                    $index = self::create_index($newstate->recursive_calls_sequence(), $newstate->state());
                    self::ensure_index_exists($states, $index->recursionlevel, $index->state, null);
                    if ($states[$index->recursionlevel][$index->state] === null ||
                            $newstate->leftmost_longest($states[$index->recursionlevel][$index->state], true, $newstate->recursion_level() === 0 && $newstate->is_full())) {
                        if ($statescount >= $this->maxstatescount) {
                            break;
                        }
                        $states[$index->recursionlevel][$index->state] = $newstate;
                        if ($newstate->startpos + $newstate->length <= $str->length()) {
                            $curstates[] = $index;
                        }

                        // If newstate is full and contains lower errors.
                        if ($newstate->is_full() && $newstate->recursion_level() === 0 && $errorscount < $this->currentmaxtypos) {
                            $this->currentmaxtypos = $errorscount;
                        }

                        ++$statescount;
                        $endstatereached = $endstatereached || ($newstate->recursion_level() === 0 && $newstate->is_full());
                    }
                }
            }
        }

        // Return array of all possible matches.
        $result = array();
        foreach ($this->automaton->get_end_states(0) as $endstate) {
            if ($states['0'][$endstate] !== null) {
                $result[] = $states['0'][$endstate];
            }
        }
        if (empty($result)) {
            $result = $partialmatches;
        }
        return $result;
    }

    protected function generate_extensions($matches, $str, $startpos) {

        //echo "\nSTART generate_extensions\n";
        //echo $this->bruteforcegeneration ? "brute force mode\n\n" : "fast mode\n\n";

        $cache = array();

        foreach ($matches as $match) {

            //echo "\nGENERATING FOR MATCH IN STATE ({$match->state()},{$match->recursive_calls_sequence()}) : {$match->str->substring(0, $startpos + $match->length)}\n";

            $match->extendedmatch = null;
            // Try each backtrack state and choose the shortest one.
            $match->backtrack_states = array_merge(array($match), $match->backtrack_states);
            foreach ($match->backtrack_states as $backtrack) {
                $backtrack->str = $backtrack->str->substring(0, $startpos + $backtrack->length);

                //echo "\nbacktrack to state ({$backtrack->state()},{$backtrack->recursive_calls_sequence()}) : {$backtrack->str}\n";

                // Look in the cache.
                $cacheindex0 = $backtrack->recursive_calls_sequence();
                $cacheindex1 = $backtrack->state();
                self::ensure_index_exists($cache, $cacheindex0, $cacheindex1, array());

                $fromcache = false;
                $tmp = null;
                foreach ($cache[$cacheindex0][$cacheindex1] as $cached) {
                    if ($cached->backtrack->equals($backtrack)){
                        //echo "TAKE FROM CACHE\n";
                        $tmp = $cached->extendedmatch;
                        $fromcache = true;
                    }
                }

                // No cached version, generate new one.
                if ($tmp === null) {
                    $tmp = $this->bruteforcegeneration
                         ? $this->generate_extension_brute_force($str, $backtrack)
                         : $this->generate_extension_fast($str, $backtrack);
                }

                // Ain't no nothing at all.
                if ($tmp === null) {
                    continue;
                }

                // Cache the generated match.
                if (!$fromcache) {
                    $tocache = new stdClass();
                    $tocache->backtrack = $backtrack;
                    $tocache->extendedmatch = $tmp;
                    $cache[$cacheindex0][$cacheindex1][] = $tocache;
                }

                //echo "result str: {$tmp->str}\n";

                // Calculate 'left'.
                $prefixlen = $startpos;
                while ($prefixlen < $match->str->length() && $prefixlen < $tmp->str->length() &&
                       $match->str[$prefixlen] == $tmp->str[$prefixlen]) {
                    $prefixlen++;
                }
                $left = $tmp->str->length() - $prefixlen;
                // Choose the best one by:
                // 1) minimizing 'left' of the generated extension
                // 2) maximizing length of the generated extension so it's as much to the original length as possible
                if (($match->extendedmatch === null) ||
                    ($match->left > $left) ||
                    ($match->left === $left && $match->extendedmatch->length < $tmp->length)) {
                    $match->extendedmatch = $tmp;
                    $match->left = $left;
                }
            }
        }
    }

    public function match_from_pos($str, $startpos) {

        if ($this->automaton->is_empty()) {
            $result = $this->create_initial_state(null, $str, $startpos);
            return $result->to_matching_results();
        }

        if ($this->options->approximatematch && $this->options->typolimit > 0) {
            if ($startpos == 0) {
                $this->currentmaxtypos = $this->options->typolimit;
            }
            $approximateenabled = true;
            $preverrorscount = $this->currentmaxtypos;
        } else {
            $this->currentmaxtypos = 0;
            $approximateenabled = false;
        }

        // Find all possible matches. Using the fast match method if there are no backreferences.
        $possiblematches = $this->bruteforcematch
                         ? $this->match_brute_force($str, $startpos)
                         : $this->match_fast($str, $startpos);

        //$matchescount = count($possiblematches);
        //echo "\n FOUND $matchescount matches\n\n";
        $fullmatchexists = false;
        if (empty($possiblematches)) {
            $result = $this->create_initial_state(null, $str, $startpos);
            if ($this->options->extensionneeded) {
                $this->generate_extensions(array($result), $str, $startpos);
            }
        } else {
            // Check if a full match was found.
            foreach ($possiblematches as $match) {
                if ($match->is_full()) {
                    $fullmatchexists = true;
                    break;
                }
            }

            // If approximate match failed set errors limit to zero.
            if (!$fullmatchexists && $approximateenabled) {
                $preverrorscount = $this->currentmaxtypos;
                $this->currentmaxtypos = 0;
            }

            // If there was no full match, generate extensions for each partial match.
            if (!$fullmatchexists && $this->options->extensionneeded) {
                $this->generate_extensions($possiblematches, $str, $startpos);
            }

            // Choose the best match.
            $result = array_pop($possiblematches);
            foreach ($possiblematches as $match) {
                if ($match->leftmost_longest($result, false)) {
                    $result = $match;
                }
            }

            // Choose the best extension. A better extension could be generated from another match.
            // But use the extention from the choosen match as the default value.
            if (!$fullmatchexists && $this->options->extensionneeded) {
                foreach ($possiblematches as $match) {
                    $ext = $match->extendedmatch;
                    if ($ext === null) {
                        continue;
                    }
                    if ($result->extendedmatch === null || $match->left < $result->left) {
                        $result->extendedmatch = $ext;
                        $result->left = $match->left;
                    }
                }
            }
        }

        // Because a partial match could be found in a recursive call, remove all
        // recursive stack items to get back to the 0-level
        $result->stack = array($result->stack[0]);

        if ($result->extendedmatch !== null) {
            $result->extendedmatch = $result->extendedmatch->to_matching_results();
            $result->extendedmatch->extendedmatch = null;   // Holy cow, this is ugly
        }

        // If result is partial bring typo limit back.
        if (!$fullmatchexists && $approximateenabled) {
            $this->currentmaxtypos = $preverrorscount;
        }

        return $result->to_matching_results();
    }

    public function get_nested_nodes($subpatt) {
        return array_key_exists($subpatt, $this->nestingmap) ? $this->nestingmap[$subpatt] : array();
    }

    protected function calculate_nesting_map($node, $currentkeys = array()) {
        if (is_a($node, 'qtype_preg_leaf')) {
            return;
        }
        foreach ($node->operands as $operand) {
            $newkeys = $operand->subpattern === -1
                     ? $currentkeys
                     : array_merge($currentkeys, array($operand->subpattern));

            $this->calculate_nesting_map($operand, $newkeys);

            if ($operand->subpattern === -1) {
                continue;
            }

            foreach ($currentkeys as $subpatt) {
                $this->nestingmap[$subpatt][] = $operand;
            }
        }
    }

    protected function calculate_backtrackstates() {
        $this->backtrackstates = $this->automaton->calculate_backtrack_states();
    }

    protected function calculate_bruteforce() {
        foreach ($this->get_nodes_with_subexpr_refs() as $node) {
            // Recursion leafs are kinda subexpr references but they don't cause bruteforce
            if ($node->type != qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL) {
                $this->bruteforcematch = true;
                $this->bruteforcegeneration = true;
                break;
            }
        }

        $this->bruteforcegeneration = $this->bruteforcegeneration && !empty($this->backtrackstates);
    }

    /**
     * Constructs an FA corresponding to the given node.
     * @return - object of \qtype_preg\fa\fa in case of success, null otherwise.
     */
    public function build_fa($dstnode, $mergeassertions = false) {
        $result = new \qtype_preg\fa\fa($this, $this->get_nodes_with_subexpr_refs());

        $stack = array();
        $dstnode->create_automaton($result, $stack, $mergeassertions);
        $body = array_pop($stack);
        $result->fastartstates[0] = array($body['start']);
        $result->faendstates[0] = array($body['end']);
        $result->calculate_subexpr_start_and_end_states();

        if ($mergeassertions) {
            $result->remove_unreachable_states();
        }
        /*global $CFG;
        $CFG->pathtodot = '/usr/bin/dot';
        $result->fa_to_dot('svg', "/home/elena/fa_1.svg");*/
        if (($body['breakpos'] !== null && empty($result->adjacencymatrix)) || empty($result->adjacencymatrix)) {
            throw new qtype_preg_empty_fa_exception('', $body['breakpos']);
        }
        $mergesuccess = true;
        $intersected = array();
        if ($mergeassertions)
        {
            // Intersect complex assertions automata.
            foreach ($result->innerautomata as $state => $inner) {
                foreach ($inner as $automaton) {
                    if (!in_array($automaton, $intersected)) {
                        $states = array();
                        if (array_key_exists($state, $result->innerautomata)) {
                            $states[] = $state;
                        }
                        foreach ($result->innerautomata as $anotherstate => &$anotherinner) {
                            foreach ($anotherinner as &$anotherautomaton) {
                                if ($automaton == $anotherautomaton && $state !== $anotherstate) {
                                    $states[] = $anotherstate;
                                    $intersected[] = $automaton;
                                }
                            }
                        }
                        /*$result->fa_to_dot('svg', "/home/elena/fa_2.svg");
                        $automaton[0]->fa_to_dot('svg', "/home/elena/fa_3.svg");*/
                        $result = $result->intersect($automaton[0], $states, $automaton[1]);
                        /*$result->fa_to_dot('svg', "/home/elena/fa_1.svg");
                        /*printf ($result->fa_to_dot());
                        return $automaton[0];*/
                    }
                }
            }
        }

        //$result->fa_to_dot('svg', "/home/elena/fa_1.svg");
        return $result;
    }

    protected function is_char_in_lang_typo_blacklist($chr) {
        if ($chr !== null && utf8_string::strpos($this->typoblacklist, $chr) !== false) {
            return true;
        }
        return false;
    }

    protected function check_for_infinite_recursion() {
        $end = $this->automaton->get_end_states();
        $front = $this->automaton->get_start_states();
        $endstatereached = false;
        while (!$endstatereached && !empty($front)) {
            $curstate = array_pop($front);
            if (in_array($curstate, $end)) {
                $endstatereached = true;
                break;
            }
            $transitions = $this->automaton->get_adjacent_transitions($curstate, true);
            foreach ($transitions as $transition) {
                if ($transition->loopsback) {
                    continue;
                }
                // Skip recursive transitions
                if ($transition->pregleaf->type == qtype_preg_node::TYPE_LEAF_SUBEXPR_CALL && $transition->pregleaf->isrecursive) {
                    continue;
                }
                $front[] = $transition->to;
            }
        }
        if (!$endstatereached) {
            $this->errors[] = new qtype_preg_error(get_string('error_infiniterecursion', 'qtype_preg'), $this->regex->string(), $this->astroot->position);
        }
    }

    protected function calculate_transpose_pseudotransitions() {
        foreach ($this->automaton->get_states() as $state) {
            foreach ($this->automaton->get_adjacent_transitions($state) as $tr1) {
                if ($tr1->pregleaf->type != qtype_preg_node::TYPE_LEAF_CHARSET) {
                    continue;
                }
                foreach ($this->automaton->get_adjacent_transitions($tr1->to) as $tr2) {
                    if ($tr2->pregleaf->type != qtype_preg_node::TYPE_LEAF_CHARSET) {
                        continue;
                    }
                    $transitions1 = array_merge($tr1->mergedbefore, array($tr1), $tr1->mergedafter);
                    $transitions2 = array_merge($tr2->mergedbefore, array($tr2), $tr2->mergedafter);
                    $this->transposepseudotransitions[$state][$tr2->to] []= array_merge($transitions1, $transitions2);
                }
            }

            if (!isset($this->transposepseudotransitions[$state])) {
                $this->transposepseudotransitions[$state] = [];
            }
        }
    }

    public function __construct($regex = null, $options = null) {
        global $CFG;

        if ($options === null) {
            $options = new qtype_preg_matching_options();
        }
        $options->replacesubexprcalls = true;
        parent::__construct($regex, $options);

        $maxstatescount = get_config('qtype_preg', 'fa_simulation_state_limit');
        if ($maxstatescount) {
            $this->maxstatescount = $maxstatescount;
        }
        $this->maxstatescount = max($this->maxstatescount, 100);

        if (!isset($regex) || !empty($this->errors)) {
            return;
        }

        // force enabling mergeassertions for approximate matching
        // TODO remove
        if ($options->approximatematch) {
            $options->mergeassertions = true;
        }

        try {
            $this->automaton = $this->build_fa($this->dstroot, $this->options->mergeassertions);
        } catch (qtype_preg_toolargefa_exception $e) {
            $this->errors[] = new qtype_preg_too_complex_error($regex, $this);
            return;
        } catch (qtype_preg_empty_fa_exception $e) {
            $this->errors[] = new qtype_preg_empty_fa_error($regex, $e->a);
            return;
        } catch (qtype_preg_backref_intersection_exception $e) {
            $this->errors[] = new qtype_preg_backref_intersection_error($regex, $e->a);
            return;
        }

        $this->check_for_infinite_recursion();
        if (!empty($this->errors)) {
            return;
        }

        $this->calculate_nesting_map($this->astroot, array($this->astroot->subpattern));
        $this->calculate_backtrackstates();
        $this->calculate_bruteforce();

        if ($options->approximatematch && $options->mergeassertions && empty($this->errors)) {
            $this->calculate_transpose_pseudotransitions();
        }

        if ($options->langid) {
            $this->langobj = \block_formal_langs::lang_object($options->langid);
            $this->typoblacklist = $this->langobj->typo_blacklist();
        }

        //echo "backtrack states:\n";
        //var_dump($this->backtrackstates);

        // Here we need to inform the automaton that 0-subexpr is represented by the AST root.
        // But for now it's implemented in other way, using the subexpr_to_subpatt array of the exec state.
        // $this->automaton->on_subexpr_added($this->astroot);
    }
}
