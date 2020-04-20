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
 * Defines Preg typo.
 *
 * @package    qtype_preg
 * @copyright  2012 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev <oasychev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
use \qtype_poasquestion\utf8_string;

class qtype_preg_typo {
    const INSERTION         = 0x0001;   // Insertion typo type.
    const DELETION          = 0x0002;   // Deletion typo type.
    const SUBSTITUTION      = 0x0004;   // Substitution typo type.
    const TRANSPOSITION     = 0x0008;   // Transposition typo type.

    /** @var int typo type */
    public $type;

    /** @var int typo position in original string */
    public $position;

    /** @var int typo position in approximate string */
    public $approximateposition;

    /** @var string typo character */
    public $char;

    /**
     * qtype_preg_typo constructor.
     * @param int $type typo type
     * @param int $pos typo position
     * @param string $char typo character
     */
    public function __construct($type, $pos, $char = '') {
        $this->type = $type;
        $this->position = $pos;
        $this->approximateposition = $pos;
        $this->char = $char;
    }

    public function __toString() {
        $str = "type = \'{$this->typo_description($this->type)}\' pos = {$this->position}";
        if ($this->type === self::INSERTION || $this->type === self::SUBSTITUTION) {
            $str .= ", char = \'{$this->char}\'";
        }
        return $str;
    }

    /**
     * Returns description for given type
     */
    public static function typo_description($type) {
        switch ($type) {
            case self::INSERTION:
                return 'insertion';
            case self::DELETION:
                return 'deletion';
            case self::SUBSTITUTION:
                return 'substitution';
            case self::TRANSPOSITION:
                return 'transposition';
        }
        return 'undefined';
    }
}

class qtype_preg_typo_container {
    /** @var int $count total errors count */
    protected $count;

    /** @var array $errors array of pairs typotype => array(qtype_preg_typo1, qtype_preg_typo2). Each typo group should always be sorted by typo position. */
    protected $errors;

    /** @var array $errorscount array of typotype => count */
    protected $errorscount;

    /** @var string $str matching string with inserted chars */
    protected $str;

    public function __construct($str) {
        $this->errors = [
                qtype_preg_typo::SUBSTITUTION => [],
                qtype_preg_typo::INSERTION => [],
                qtype_preg_typo::DELETION => [],
                qtype_preg_typo::TRANSPOSITION => [],
        ];
        $this->errorscount = [
            qtype_preg_typo::SUBSTITUTION => 0,
            qtype_preg_typo::INSERTION => 0,
            qtype_preg_typo::DELETION => 0,
            qtype_preg_typo::TRANSPOSITION => 0,
        ];
        $this->str = is_a($str, '\qtype_poasquestion\utf8_string') ? $str->string() : $str;
        $this->count = 0;
    }

    public function contains($type, $pos, $char = null) {
        $comparebychar = $type !== qtype_preg_typo::TRANSPOSITION && $type !== qtype_preg_typo::DELETION && $char !== null && strlen($char) > 0;

        foreach ($this->errors[$type] as $typo) {
            if ($typo->position > $pos) {
                return false;
            }
            if ($typo->position === $pos && (!$comparebychar || strcmp($typo->char, $char) === 0)) {
                return true;
            }
        }
        return false;
    }

    public function contains_approximate($type, $approximatepos, $char = null) {
        $comparebychar = $type !== qtype_preg_typo::TRANSPOSITION && $type !== qtype_preg_typo::DELETION && $char !== null && strlen($char) > 0;

        foreach ($this->errors[$type] as $typo) {
            if ($typo->approximateposition > $approximatepos) {
                return false;
            }
            if ($typo->approximateposition === $approximatepos && (!$comparebychar || strcmp($typo->char, $char) === 0)) {
                return true;
            }
        }
        return false;
    }

    protected function get($type, $approximatepos, $char = null) {
        $comparebychar = $type !== qtype_preg_typo::TRANSPOSITION && $type !== qtype_preg_typo::DELETION && $char !== null && strlen($char) > 0;

        foreach ($this->errors[$type] as $typo) {
            if ($typo->approximateposition > $approximatepos) {
                return null;
            }
            if ($typo->approximateposition === $approximatepos && (!$comparebychar || strcmp($typo->char, $char) === 0)) {
                return $typo;
            }
        }
        return null;
    }

    public function __toString() {
        $result = "";
        foreach ($this->errors as $type => $errors) {
            if (count($errors)) {
                $result.= "\t" . qtype_preg_typo::typo_description($type) . "s:\n";
            }
            foreach($errors as $err) {
                $result.= "\t\tpos = {$err->position}, char = {$err->char}" . "\n";
            }
        }
        return $result;
    }

    /** Returns count of chosen errors.
     * @param int $type
     * @return int
     */
    public function count($type = -1) {
        if ($type === -1) {
            return $this->count;
        }

        // If only 1 type.
        if ($type & ($type - 1) == 0) {
            return $this->errorscount[$type];
        }

        $result = 0;
        foreach ($this->errors as $key => $value) {
            if ($type & $key) {
                $result += count($value);
            }
        }
        return $result;
    }

    /**
     * Add typo to container
     */
    public function add($typo) {
        $typo->approximateposition = $typo->position + $this->errorscount[qtype_preg_typo::INSERTION];
        $this->errors[$typo->type] [] = $typo;
        $this->count++;
        ++$this->errorscount[$typo->type];
        if ($typo->type === qtype_preg_typo::INSERTION) {
            $this->str = utf8_string::substr($this->str, 0, $typo->approximateposition) . $typo->char . utf8_string::substr($this->str, $typo->approximateposition);
        }
    }

    protected function add_inner($typo) {
        // calculate approximate position
        $approximatepos = $typo->position;
        foreach ($this->errors[qtype_preg_typo::INSERTION] as $ins) {
            if ($ins->position > $typo->position) {
                break;
            }
            ++$approximatepos;
        }
        $typo->approximateposition = $approximatepos;

        // calc position in target array
        $posinarray = 0;
        foreach ($this->errors[$typo->type] as $idx => $ext) {
            if ($ext->approximateposition > $typo->approximateposition) {
                break;
            }
            $posinarray = $idx + 1;
        }

        if ($typo->type === qtype_preg_typo::INSERTION) {
            // move other typos
            foreach ($this->errors as $typos) {
                foreach ($typos as $exttypo) {
                    if ($exttypo->approximateposition >= $typo->approximateposition) {
                        ++$exttypo->approximateposition;
                    }
                }
            }
            // change approximate string
            $this->str = utf8_string::substr($this->str, 0, $typo->approximateposition) . $typo->char . utf8_string::substr($this->str, $typo->approximateposition);
        }

        array_splice($this->errors[$typo->type], $posinarray, 0, [$typo]);
        $this->errorscount[$typo->type] = count($this->errors[$typo->type]);
        $this->count = array_sum($this->errorscount);
    }

    public function typos() {
        return $this->errors;
    }

    public function approximate_string() {
        return $this->str;
    }

    /** Apply all typos to given string
     * @param string $string
     * @param $ignoredtypes Ingnored typo types.
     * @return string string with applyied typos
     */
    public function apply($ignoredtypos = 0) {
        $applydeletions = ($ignoredtypos & qtype_preg_typo::DELETION) == 0;
        $applyinsertions = ($ignoredtypos & qtype_preg_typo::INSERTION) == 0;
        $applytranspositions = ($ignoredtypos & qtype_preg_typo::TRANSPOSITION) == 0;
        $applysubstitutions = ($ignoredtypos & qtype_preg_typo::SUBSTITUTION) == 0;

        $originalstring = $this->str;
        $result = "";
        for ($pos = 0;; ++$pos)
        {
            // Apply transposition.
            if ($applytranspositions) {
                $typo = $this->get(qtype_preg_typo::TRANSPOSITION, $pos);
                if ($typo !== null) {
                    $tmp1 = utf8_string::substr($originalstring, $pos,1);
                    $tmp2 = utf8_string::substr($originalstring,$pos + 1,1);
                    $result .= $tmp2 . $tmp1;
                    ++$pos;
                    continue;
                }
            }

            // Apply substitutions.
            if ($applysubstitutions) {
                $typo = $this->get(qtype_preg_typo::SUBSTITUTION, $pos);
                if ($typo !== null) {
                    $result .= $typo->char;
                    continue;
                }
            }

            // Apply deletion.
            if ($applydeletions) {
                $typo = $this->get(qtype_preg_typo::DELETION, $pos);
                if ($typo !== null) {
                    // Do nothing
                    continue;
                }
            }

            // By defalt approximate string contains applyed insertions, so behaviour same as deletion if $applyinsertions disabled
            if (!$applyinsertions) {
                $typo = $this->get(qtype_preg_typo::INSERTION, $pos);
                if ($typo !== null) {
                    // Do nothing
                    continue;
                }
            }

            $char = utf8_string::substr($originalstring, $pos, 1);
            if ($char === "") {
                break;
            }
            $result .= $char;
        }

        return $result;
    }

    public function to_lexem_label_format() {
        $originalstring = $this->str;
        $result = "";
        $operations = [];
        for ($pos = 0;; ++$pos)
        {
            // Apply transposition.
            $typo = $this->get(qtype_preg_typo::TRANSPOSITION, $pos);
            if ($typo !== null) {
                $tmp1 = utf8_string::substr($originalstring, $pos,1);
                $tmp2 = utf8_string::substr($originalstring,$pos + 1,1);
                $result .= $tmp2 . $tmp1;
                $operations []= 'transpose';
                $operations []= 'transpose';
                ++$pos;
                continue;
            }

            // Apply deletion.
            $typo = $this->get(qtype_preg_typo::DELETION, $pos);
            if ($typo !== null) {
                $operations []= 'strikethrough';
                $result .= utf8_string::substr($originalstring, $pos,1);
                continue;
            }

            // Apply insertion.
            $typo = $this->get(qtype_preg_typo::INSERTION, $pos);
            if ($typo !== null) {
                $operations []= 'insert';
                $result .= utf8_string::substr($originalstring, $pos,1);
                continue;
            }

            // Apply substitution (as deletion and insertion).
            $typo = $this->get(qtype_preg_typo::SUBSTITUTION, $pos);
            if ($typo !== null) {
                // deletion
                $operations []= 'strikethrough';
                $result .= utf8_string::substr($originalstring, $pos,1);

                // insertion
                $operations []= 'insert';
                $result .= $typo->char;
                continue;
            }

            $char = utf8_string::substr($originalstring, $pos, 1);
            if ($char === "") {
                break;
            }

            $result .= $char;
            $operations []= 'normal';
        }

        return array($result, $operations);
    }

}



