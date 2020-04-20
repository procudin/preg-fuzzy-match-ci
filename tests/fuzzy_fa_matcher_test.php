<?php


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/tests/cross_tester.php');

set_config('fa_transition_limit', 10000, 'qtype_preg');
set_config('fa_state_limit', 10000, 'qtype_preg');

use \qtype_poasquestion\utf8_string;

class qtype_preg_fuzzy_fa_cross_tester extends qtype_preg_cross_tester {

    /** @var Array of passed full match tests*/
    protected $passednormaltests = [];

    /** @var Logfile's name*/
    protected $logfilename;

    public function engine_name() {
        return 'fa_matcher';
    }

    public function setUp()
    {
        $this->logfilename = __DIR__  . "/errorslog_" . date('Y_m_d___H_i_s') . ".txt";
    }

    public function log($message) {
        file_put_contents($this->logfilename, $message, FILE_APPEND);
    }

    public function accept_regex($regex) {
        return !preg_match('/\\\\\d+|\*\?|\+\?|\?\?|\}\?|\\\\g|\(\?\=|\(\?\!|\(\?\<\=|\(\?\<\!|\(\?/', $regex);
    }

    protected function serialize_test_data($filename) {
        $serialized = serialize($this->passednormaltests);
        mb_internal_encoding('UTF-8');
        file_put_contents($filename, $serialized);
        $result = 'Data Added ok!!';
        return $result;
    }

    protected function unserialize_test_data($filename) {
        $content = file_get_contents($filename);
        $this->passednormaltests = unserialize($content);
    }

    protected function run_fuzzy_tests() {
        $passcount = 0;
        $failcount = 0;
        $skipcount = 0;

        $options = new qtype_preg_matching_options();  // Forced subexpression catupring.
        $blacklist = array_merge($this->blacklist_tags(), $this->blacklist);

        echo "Test fuzzy matching:\n";

        foreach ($this->passednormaltests as $data) {
            // Get current test data.
            $regex = $data['regex'];
            $modifiersstr = '';
            $regextags = array();
            $notation = self::NOTATION_NATIVE;
            if (array_key_exists('modifiers', $data)) {
                $modifiersstr = $data['modifiers'];
            }
            if (array_key_exists('tags', $data)) {
                $regextags = $data['tags'];
            }
            if (array_key_exists('notation', $data)) {
                $notation = $data['notation'];
            }

            $regextags [] = self::TAG_DONT_CHECK_PARTIAL;
            $regextags [] = self::TAG_ALLOW_FUZZY;

            $matcher_merged = null;
            $matcher_unmerged = null;

            foreach ($data['tests'] as $expected) {
                // Generate tests for fuzzy matching
                $fuzzytests = $this->make_errors($expected);

                foreach ($fuzzytests as $fuzzyexpected) {
                    $str = $fuzzyexpected['str'];
                    $strtags = array();
                    if (array_key_exists('tags', $fuzzyexpected)) {
                        $strtags = $fuzzyexpected['tags'];
                    }

                    $tags = array_merge($regextags, $strtags);

                    // Create matcher
                    $timestart = round(microtime(true) * 1000);
                    $options->mode = in_array(self::TAG_MODE_POSIX, $regextags) ? qtype_preg_handling_options::MODE_POSIX : qtype_preg_handling_options::MODE_PCRE;
                    $options->modifiers = qtype_preg_handling_options::string_to_modifiers($modifiersstr);
                    $options->debugmode = in_array(self::TAG_DEBUG_MODE, $regextags);
                    $options->approximatematch = true;
                    $options->langid = null;
                    $options->typolimit = (int)(!isset($fuzzyexpected['errorslimit']) ? 0 : $fuzzyexpected['errorslimit']);
                    $options->mergeassertions = in_array(self::TAG_FAIL_MODE_MERGE, $tags) || isset($fuzzyexpected['typos']) && array_key_exists(8, $fuzzyexpected['typos']);
                    $options->extensionneeded = !in_array(self::TAG_DONT_CHECK_PARTIAL, $regextags);
                    $matcher = $this->get_matcher($this->engine_name(), $regex, $options);
                    $timeend = round(microtime(true) * 1000);
                    if ($timeend - $timestart > self::MAX_BUILDING_TIME) {
                        $message = "\nSlow building on regex : '$regex', str : '$str', errorslimit : {$matcher->get_options()->typolimit}";
                        $this->log($message);
                        $slowbuildtests[] = $message;
                    }


                    $timestart = round(microtime(true) * 1000);
                    try {
                        $matcher->match($str);
                        $obtained = $matcher->get_match_results();
                    } catch (Exception $e) {
                        $message = "\nFailed matching on regex '$regex' and string '$str', errorslimit : {$matcher->get_options()->typolimit}";
                        $this->log($message);
                        continue;
                    }
                    $timeend = round(microtime(true) * 1000);
                    if ($timeend - $timestart > self::MAX_BUILDING_TIME) {
                        $message = "\nSlow match on regex : '$regex', str : '$str', errorslimit : {$matcher->get_options()->typolimit}";
                        $this->log($message);
                        $slowmatchtests[] = $message;
                    }

                    // Results obtained, check them.
                    try {
                        if ($this->compare_better_or_equal($regex, $str, $modifiersstr, $tags, $matcher, $fuzzyexpected, $obtained, true)) {
                            $passcount++;
                        } else {
                            $failcount++;
                        }
                    } catch (Exception $e) {
                        $message = "\nFailed error applying on regex '$regex' and string '$str', applying typos: \n{$obtained->typos}";
                        $this->log($message);
                    }
                }
            }
        }
        if ($failcount == 0 && empty($exceptiontests) && $passcount > 0) {
            echo "\n\nWow! All tests passed!\n\n";
        }
        echo "======================\n";
        echo 'PASSED:     ' . $passcount . "\n";
        echo 'FAILED:     ' . $failcount . "\n";
        echo 'SKIPPED:    ' . $skipcount . "\n";
        echo "======================\n";
        if (!empty($slowbuildtests)) {
            echo "tests with slow matcher building:\n";
            echo implode("\n", $slowbuildtests) . "\n";
            echo "======================\n";
        }
        if (!empty($slowmatchtests)) {
            echo "tests with slow matching:\n";
            echo implode("\n", $slowmatchtests) . "\n";
            echo "======================\n";
        }
        if (!empty($exceptiontests)) {
            echo "tests with unhandled exceptions:\n";
            echo implode("\n", $exceptiontests) . "\n";
            echo "======================\n";
        }
    }

    protected function compare_better_or_equal_by_errors($expected, $obtained, &$equalserrorscount = false , &$equalserrors = false, &$leftmostlongest = false, &$betterbypriorty = false) {
        $equalserrorscount = false;
        $equalserrors = false;
        $leftmostlongest = false;
        $betterbypriorty = false;

        $expectederrorscount = isset($expected['errorscount']) ? $expected['errorscount'] : 0;

        // Check by typos count.
        if ($expectederrorscount > $obtained->typos->count()) {
            return true;
        } else if ($expectederrorscount < $obtained->typos->count()) {
            return false;
        }

        // Check typos by equals.
        $equalserrors = true;
        $equalserrorscount = true;
        $expectederrors = isset($expected['typos']) ? $expected['typos'] : [];
        foreach ($expectederrors as $type => $errors) {
            if (!$equalserrors) {
                break;
            }
            foreach ($errors as $err) {
                $equalserrors = $equalserrors && $obtained->typos->contains($type, $err['pos'], $err['char']);
            }
        }

        // Check by leftmostlongest.
        $leftmost = array_key_exists(0, $expected['index_first']) && $expected['index_first'][0] > $obtained->indexfirst[0];

        if ($leftmost) {
            return $leftmostlongest = true;
        }
        if ($equalserrors) {
            return true;
        }

        $equalindexfirst = (array_key_exists(0, $expected['index_first']) && $expected['index_first'][0] === $obtained->indexfirst[0]);
        $longest = array_key_exists(0, $expected['length']) && $expected['length'][0] < $obtained->length[0];
        $longestorequal = $longest || (array_key_exists(0, $expected['length']) && $expected['length'][0] === $obtained->length[0]);
        $leftmostlongest = $equalindexfirst && $longestorequal;
        if (!$leftmostlongest) {
            return false;
        }

        // Check by typo priority.
        if (!$longest) {
            if (!isset($expectederrors[qtype_preg_typo::TRANSPOSITION]) && $obtained->typos->count(qtype_preg_typo::TRANSPOSITION) > 0
                    || isset($expectederrors[qtype_preg_typo::TRANSPOSITION]) && count($expectederrors[qtype_preg_typo::TRANSPOSITION]) < $obtained->typos->count(qtype_preg_typo::TRANSPOSITION)) {
                return true;
            } else if (isset($expectederrors[qtype_preg_typo::TRANSPOSITION]) && count($expectederrors[qtype_preg_typo::TRANSPOSITION]) > $obtained->typos->count(qtype_preg_typo::TRANSPOSITION)) {
                return false;
            }
            if (!isset($expectederrors[qtype_preg_typo::SUBSTITUTION]) && $obtained->typos->count(qtype_preg_typo::SUBSTITUTION) > 0
                    || isset($expectederrors[qtype_preg_typo::SUBSTITUTION]) && count($expectederrors[qtype_preg_typo::SUBSTITUTION]) < $obtained->typos->count(qtype_preg_typo::SUBSTITUTION)) {
                return true;
            } else if (isset($expectederrors[qtype_preg_typo::SUBSTITUTION]) && count($expectederrors[qtype_preg_typo::SUBSTITUTION]) > $obtained->typos->count(qtype_preg_typo::SUBSTITUTION)) {
                return false;
            }
            if (!isset($expectederrors[qtype_preg_typo::DELETION]) && $obtained->typos->count(qtype_preg_typo::DELETION) > 0
                    || isset($expectederrors[qtype_preg_typo::DELETION]) && count($expectederrors[qtype_preg_typo::DELETION]) < $obtained->typos->count(qtype_preg_typo::DELETION)) {
                return true;
            } else if (isset($expectederrors[qtype_preg_typo::DELETION]) && count($expectederrors[qtype_preg_typo::DELETION]) > $obtained->typos->count(qtype_preg_typo::DELETION)) {
                return false;
            }
            if (!isset($expectederrors[qtype_preg_typo::INSERTION]) && $obtained->typos->count(qtype_preg_typo::INSERTION) > 0
                    || isset($expectederrors[qtype_preg_typo::INSERTION]) && count($expectederrors[qtype_preg_typo::INSERTION]) < $obtained->typos->count(qtype_preg_typo::INSERTION)) {
                return true;
            } else if (isset($expectederrors[qtype_preg_typo::INSERTION]) && count($expectederrors[qtype_preg_typo::INSERTION]) > $obtained->typos->count(qtype_preg_typo::INSERTION)) {
                return false;
            }
        }

        return $leftmostlongest;
    }

    protected function compare_better_or_equal($regex, $str, $modstr, $tags, $matcher, $expected, $obtained, $dumpfails) {
        // Do some initialization.
        $fullpassed = ($expected['full'] === $obtained->full);
        $indexfirstpassed = true;
        $lengthpassed = true;
        $equalserrorscount = true;
        $equalserrors = true;
        $leftmostlongest = true;

        $expectederrorscount = isset($expected['errorscount']) ? $expected['errorscount'] : 0;
        $expectederrorslimit = isset($expected['errorslimit']) ? $expected['errorslimit'] : 0;
        $expectederrors = isset($expected['typos']) ? $expected['typos'] : [];

        $lowererrcount = $obtained->typos->count() < $expectederrorscount;

        $isbetter = $fullpassed && !$obtained->full;
        $isbetter = $isbetter || !$expected['full'] && $obtained->full;
        $isbetter = $isbetter || $lowererrcount;
        $isbetter = $isbetter || $this->compare_better_or_equal_by_errors($expected, $obtained , $equalserrorscount,$equalserrors,$leftmostlongest);

        $checkindexes = $isbetter && $expected['full'] && ($equalserrors) && !$lowererrcount && !$leftmostlongest;
        // Match existance, indexes and lengths.
        if ($checkindexes) {
            $subexprsupported = $matcher->is_supporting(qtype_preg_matcher::SUBEXPRESSION_CAPTURING);
            foreach ($obtained->indexfirst as $key => $index) {
                if (!$subexprsupported && $key != 0) {
                    continue;
                }
                $indexfirstpassed = $indexfirstpassed && ((!array_key_exists($key, $expected['index_first']) && $index === qtype_preg_matching_results::NO_MATCH_FOUND) ||
                                (array_key_exists($key, $expected['index_first']) && $expected['index_first'][$key] === $obtained->indexfirst[$key]));
            }
            foreach ($obtained->length as $key => $index) {
                if (!$subexprsupported && $key != 0) {
                    continue;
                }
                $lengthpassed = $lengthpassed && ((!array_key_exists($key, $expected['length']) && $index === qtype_preg_matching_results::NO_MATCH_FOUND) ||
                                (array_key_exists($key, $expected['length']) && $expected['length'][$key] === $obtained->length[$key]));
            }
        }

        // Apply typos to string && run normal match.
        $errorsapplyed = false;
        if ($isbetter && $obtained->full) {
            $strafterapplying = $obtained->typos->apply();

            $matcher->get_options()->typolimit = 0;

            $obtainedafterapplying = $matcher->match($strafterapplying);

            $fullpassedafteraaplying = $obtainedafterapplying->full;
            $errorsapplyed = true;
        }


        $passed = $isbetter && $indexfirstpassed && $lengthpassed &&
                (!$errorsapplyed || $fullpassedafteraaplying);

        if (!$passed && $dumpfails) {
            $obtainedstr = '';
            $expectedstr = '';

            if (!$isbetter) {
                if (!$fullpassed) {
                    $obtainedstr .= $this->dump_boolean('FULL:            ', $obtained->full);
                    $expectedstr .= $this->dump_boolean('FULL:            ', $expected['full']);
                }

                if (!$equalserrorscount) {
                    $obtainedstr .= $this->dump_scalar('TYPOS COUNT:        ', $obtained->typos->count());
                    $expectedstr .= $this->dump_scalar('TYPOS COUNT:        ', $expectederrorscount);
                }

                if (!$equalserrors && !$leftmostlongest) {
                    $obtainedstr .= "TYPOS:        \n" . $obtained->typos;
                    $expectedstr .= "TYPOS:        \n" . $this->dump_errors($expectederrors);
                }
            }

            if ($errorsapplyed && !$fullpassedafteraaplying) {
                $obtainedstr .= $this->dump_boolean('FULL AFTER TYPOS APPLYING:            ', $obtainedafterapplying->full);
                $expectedstr .= $this->dump_boolean('FULL AFTER TYPOS APPLYING:            ', true);
            }

            if ($checkindexes) {
                // index_first
                if (!$indexfirstpassed) {
                    $obtainedstr .= $this->dump_indexes('INDEX_FIRST:     ', $obtained->indexfirst);
                    $expectedstr .= $this->dump_indexes('INDEX_FIRST:     ', $expected['index_first']);
                }

                // length
                if (!$lengthpassed) {
                    $obtainedstr .= $this->dump_indexes('LENGTH:          ', $obtained->length);
                    $expectedstr .= $this->dump_indexes('LENGTH:          ', $expected['length']);
                }
            }

            $enginename = $matcher->name();
            $merging = in_array(self::TAG_FAIL_MODE_MERGE, $tags) ? "merging is on" : "merging is off";

            $message = $modstr == '' ?
                    "$enginename failed on regex '$regex' and string '$str', $merging with errorslimit : $expectederrorslimit:\n" :
                    "$enginename failed on regex '$regex' string '$str' and modifiers '$modstr', $merging with errorslimit : $expectederrorslimit:\n";
            $message .= $obtainedstr;
            $message .= "expected:\n";
            $message .= $expectedstr;
            $message .= "\n";

            echo $message;
            $this->log($message);
        }

        // Return true if everything is correct, false otherwise.
        return $passed;
    }

    function dump_errors($values) {
        $result = "";
        foreach ($values as $type => $errors) {
            if (count($errors)) {
                $result .= "\t" . qtype_preg_typo::typo_description($type) . "s:\n";
            }
            foreach ($errors as $err) {
                $result .= "\t\tpos = {$err['pos']}, char = {$err['char']}" . "\n";
            }
        }
        return $result;
    }

    protected function make_errors($testdata) {
        $result = [];

        $result = array_merge($result, $this->make_simple_errors($testdata));
        $result = array_merge($result, $this->make_nearby_errors($testdata));
        $result = array_merge($result, $this->make_consecutive_errors($testdata));

        return $result;
    }

    protected function make_simple_errors($testdata) {
        $result = [];

        $str = $testdata['str'];
        $ind0 = $testdata['index_first'][0];
        $len0 = $testdata['length'][0];

        // Test data without modification, same as normal matching.
        $tmpdata = $testdata;
        $tmpdata['errorslimit'] = 1;
        $result [] = $tmpdata;

        // Test data without modification with bigger error limit, same as normal matching.
        $tmpdata = $testdata;
        $tmpdata['errorslimit'] = mt_rand(2, 4);
        $result [] = $tmpdata;

        // If string too short.
        if ($len0 < 2) {
            return $result;
        }

        // Test data with 1 substitution.
        $ind = $this->generate_unique_random_number($ind0, $ind0 + $len0 - 1);
        $result [] = $this->create_error($testdata, qtype_preg_typo::SUBSTITUTION, $ind, chr(mt_rand(0, 127)));

        // Test data with 1 insertion.
        $ind = $this->generate_unique_random_number($ind0, $ind0 + $len0 - 1);
        $result [] = $this->create_error($testdata, qtype_preg_typo::INSERTION, $ind, chr(mt_rand(0, 127)));

        // Test data with 1 deletion.
        $ind = $this->generate_unique_random_number($ind0, $ind0 + $len0 - 1);
        $result [] = $this->create_error($testdata, qtype_preg_typo::DELETION, $ind, chr(mt_rand(0, 127)));

        // Test data with 1 transposition.
        $ind = $this->generate_unique_random_number($ind0, $ind0 + $len0 - 2);
        $result [] = $this->create_error($testdata, qtype_preg_typo::TRANSPOSITION, $ind);

        // Test data with 1 random error and 0-error limit (should fails or returns better result).
        $ind = $this->generate_unique_random_number($ind0, $ind0 + $len0 - 2);
        $tmpdata = $this->create_error($testdata, 2 ** mt_rand(0, 3), $ind, mt_rand(1, 127));
        $tmpdata['is_match'] = false;
        $tmpdata['full'] = false;
        $tmpdata['errorslimit'] = 0;
        $result [] = $tmpdata;

        return $result;
    }

    protected function make_nearby_errors($testdata) {
        $result = [];

        $ind0 = $testdata['index_first'][0];
        $len0 = $testdata['length'][0];

        // If string too short.
        if ($len0 < 5) {
            return $result;
        }

        // Generate all error pairs.
        for ($i = 1; $i <= 8; $i *= 2) {
            for ($j = 1; $j <= 8; $j *= 2) {
                $rightborder = ($i == 8 && $j == 8) ? 4 : (($i == 8 || $j == 8) ? 3 : 2);
                $ind1 = $this->generate_unique_random_number($ind0, $ind0 + $len0 - $rightborder);
                $data = $this->create_error($testdata, $i, $ind1, chr(mt_rand(0, 127)));
                $data = $this->create_error($data, $j, $ind1 + ($i == 8 ? 2 : (($i == 1) ? 0 : 1)), chr(mt_rand(0, 127)));
                $result [] = $data;
            }
        }

        return $result;
    }

    protected function make_consecutive_errors($testdata) {
        $result = [];

        $ind0 = $testdata['index_first'][0];
        $len0 = $testdata['length'][0];

        // If string too short.
        if ($len0 < 5) {
            return $result;
        }

        // Insertions, deletions, substitutions.
        for ($i = 1; $i <= 4; $i *= 2) {
            $count = $this->generate_unique_random_number(0, ($len0 <= 6) ? $len0 : 6);
            $first = $this->generate_unique_random_number($ind0, $ind0 + $len0 - $count);
            $data = $testdata;
            for ($j = 0; $j < $count; $j++) {
                $data = $this->create_error($data, $i, $first, chr(mt_rand(0, 127)));
                if ($i != 1) {
                    $first++;
                }
            }
            $result [] = $data;
        }

        // Transpositions.
        $count = $this->generate_unique_random_number(0, ($len0 / 2 <= 6) ? $len0 / 2 : 6);
        $first = $this->generate_unique_random_number($ind0, $ind0 + $len0 - $count * 2);
        $data = $testdata;
        for ($i = 0; $i < $count; $i++, $first += 2) {
            $data = $this->create_error($data, qtype_preg_typo::TRANSPOSITION, $first, chr(mt_rand(0, 127)));
        }
        $result [] = $data;

        return $result;
    }

    protected function generate_unique_random_number($from, $to, $oldnumbers = null, $typotype = -1) {
        $numb = mt_rand($from, $to);

        if ($oldnumbers === null) {
            return $numb;
        }

        if (!array_key_exists($numb, $oldnumbers)) {
            if ($typotype === qtype_preg_typo::TRANSPOSITION) {
                if (!array_key_exists($numb + 1, $oldnumbers)) {
                    $oldnumbers[$numb] = $numb;
                    $oldnumbers[$numb + 1] = $numb + 1;
                    return $numb;
                }
            } else {
                $oldnumbers[$numb] = $numb;
                return $numb;
            }
        }

        for ($numb = $from; $numb <= $to; $numb++) {
            if (!array_key_exists($numb, $oldnumbers)) {
                if ($typotype === qtype_preg_typo::TRANSPOSITION) {
                    if (!array_key_exists($numb + 1, $oldnumbers)) {
                        $oldnumbers[$numb] = $numb;
                        $oldnumbers[$numb + 1] = $numb + 1;
                        return $numb;
                    }
                } else {
                    $oldnumbers[$numb] = $numb;
                    return $numb;
                }
            }
        }

        return null;
    }

    protected function create_error($testdata, $errtype = -1, $pos = -1, $char = '') {
        $str = $testdata['str'];
        $newstr = $str;
        $result = $testdata;

        switch ($errtype) {
            case qtype_preg_typo::SUBSTITUTION:
                $newstr = utf8_string::substr($newstr, 0, $pos) . $char . utf8_string::substr($newstr, $pos + 1);
                $result['typos'][qtype_preg_typo::SUBSTITUTION] [] = ['pos' => $pos, 'char' => $str[$pos]];
                break;
            case qtype_preg_typo::INSERTION:
                // Delete insertable char
                $newstr = utf8_string::substr($newstr, 0, $pos) . utf8_string::substr($newstr, $pos + 1);
                $result['typos'][qtype_preg_typo::INSERTION] [] = ['pos' => $pos, 'char' => $str[$pos]];
                break;
            case qtype_preg_typo::DELETION:
                // Insert deletable char
                $newstr = utf8_string::substr($newstr, 0, $pos) . $char . utf8_string::substr($newstr, $pos);
                $result['typos'][qtype_preg_typo::DELETION] [] = ['pos' => $pos, 'char' => $char];
                break;
            case qtype_preg_typo::TRANSPOSITION:
                $tmp1 = utf8_string::substr($newstr, $pos, 1);
                $tmp2 = utf8_string::substr($newstr, $pos + 1, 1);
                $newstr = utf8_string::substr($newstr, 0, $pos) . $tmp2 . $tmp1 . utf8_string::substr($newstr, $pos + 2);
                $result['typos'][qtype_preg_typo::TRANSPOSITION] [] = ['pos' => $pos, 'char' => $char];
                break;
        }

        $result['str'] = $newstr;
        if (!isset($result['errorscount'])) {
            $result['errorscount'] = 0;
        }
        $result['errorscount']++;

        if (!isset($result['errorslimit'])) {
            $result['errorslimit'] = 0;
        }
        $result['errorslimit']++;

        // Random errorslimit increase.
        if (mt_rand(0, 4) === 0) {
            $result['errorslimit']++;
        }

        // Update submatches.
        if ($errtype === qtype_preg_typo::DELETION) {
            foreach ($result['index_first'] as $key => $value) {
                if ($pos > $result['index_first'][$key] && $pos < $result['index_first'][$key] + $result['length'][$key]) {
                    $result['length'][$key]++;
                }
            }
            foreach ($result['index_first'] as $key => $value) {
                if ($pos <= $result['index_first'][$key]) {
                    $result['index_first'][$key]++;
                }
            }

        }
        if ($errtype === qtype_preg_typo::INSERTION) {
            foreach ($result['index_first'] as $key => $value) {
                if ($pos >= $result['index_first'][$key] && $pos < $result['index_first'][$key] + $result['length'][$key]) {
                    $result['length'][$key]--;
                }
            }
            foreach ($result['index_first'] as $key => $value) {
                if ($pos < $result['index_first'][$key]) {
                    $result['index_first'][$key]--;
                }
            }
        }

        return $result;
    }

    public function run_normal_tests() {
        $passcount = 0;
        $failcount = 0;
        $skipcount = 0;

        $slowbuildtests = array();
        $slowmatchtests = array();
        $exceptiontests = array();

        $options = new qtype_preg_matching_options();  // Forced subexpression catupring.
        $blacklist = array_merge($this->blacklist_tags(), $this->blacklist);

        echo "Test full and partial matching:\n";

        foreach ($this->testdataobjects as $testdataobj) {
            $testmethods = get_class_methods($testdataobj);
            $classname = get_class($testdataobj);
            foreach ($testmethods as $methodname) {
                // Filtering class methods by names. A test method name should start with 'data_for_test_'.
                if (strpos($methodname, 'data_for_test_') !== 0) {
                    continue;
                }

                // Get current test data.
                $data = $testdataobj->$methodname();
                $regex = $data['regex'];
                $modifiersstr = '';
                $regextags = array();
                $notation = self::NOTATION_NATIVE;
                if (array_key_exists('modifiers', $data)) {
                    $modifiersstr = $data['modifiers'];
                }
                if (array_key_exists('tags', $data)) {
                    $regextags = $data['tags'];
                }
                if (array_key_exists('notation', $data)) {
                    $notation = $data['notation'];
                }

                // Skip empty regexes
                if ($regex == '') {
                    continue;
                }

                // Skip regexes with blacklisted tags.
                if (count(array_intersect($blacklist, $regextags)) > 0) {
                    continue;
                }

                $matcher_merged = null;
                $matcher_unmerged = null;

                $passeddata = $testdataobj->$methodname();
                $passeddata['tests'] = [];

                // Iterate over all tests.
                foreach ($data['tests'] as $expected) {
                    $str = $expected['str'];
                    $strtags = array();
                    if (array_key_exists('tags', $expected)) {
                        $strtags = $expected['tags'];
                    }

                    $tags = array_merge($regextags, $strtags);

                    // Skip tests with blacklisted tags.
                    if (count(array_intersect($blacklist, $tags)) > 0) {
                        continue;
                    }

                    // Lazy matcher building.
                    $merge = in_array(self::TAG_FAIL_MODE_MERGE, $tags);
                    if (($merge && $matcher_merged === null) || (!$merge && $matcher_unmerged === null)) {
                        $timestart = round(microtime(true) * 1000);
                        $options->mode = in_array(self::TAG_MODE_POSIX, $regextags) ? qtype_preg_handling_options::MODE_POSIX : qtype_preg_handling_options::MODE_PCRE;
                        $options->modifiers = qtype_preg_handling_options::string_to_modifiers($modifiersstr);
                        $options->debugmode = in_array(self::TAG_DEBUG_MODE, $regextags);
                        $options->mergeassertions = $merge;
                        $options->extensionneeded = !in_array(self::TAG_DONT_CHECK_PARTIAL, $regextags);
                        $tmpmatcher = $this->get_matcher($this->engine_name(), $regex, $options);
                        $timeend = round(microtime(true) * 1000);
                        if ($timeend - $timestart > self::MAX_BUILDING_TIME) {
                            $slowbuildtests[] = $classname . ' : ' . $methodname;
                        }

                        if ($merge) {
                            $matcher_merged = $tmpmatcher;
                        } else {
                            $matcher_unmerged = $tmpmatcher;
                        }
                    }

                    $matcher = $merge ? $matcher_merged : $matcher_unmerged;

                    // Move to the next test if there's something wrong.
                    if ($matcher === null || $this->check_for_errors($matcher)) {
                        ++$skipcount;
                        continue;
                    }

                    // There can be exceptions during matching.
                    $timestart = round(microtime(true) * 1000);
                    try {
                        $matcher->match($str);
                        $obtained = $matcher->get_match_results();
                    } catch (Exception $e) {
                        echo "EXCEPTION CATCHED DURING MATCHING, test name is " . $methodname .  "\n" . $e->getMessage() . "\n";
                        $exceptiontests[] = $classname . ' : ' . $methodname;
                        continue;
                    }
                    $timeend = round(microtime(true) * 1000);
                    if ($timeend - $timestart > self::MAX_BUILDING_TIME) {
                        $slowmatchtests[] = $classname . ' : ' . $methodname;
                    }

                    // Results obtained, check them.
                    $skippartialcheck = in_array(self::TAG_DONT_CHECK_PARTIAL, $tags);
                    if ($this->compare_results($regex, $notation, $str, $modifiersstr, $tags, $matcher, $expected, $obtained, $classname, $methodname, $skippartialcheck, true)) {
                        $passcount++;

                        if ($expected['is_match'] && $expected['full']) {
                            $passeddata['tests'] [] = $expected;
                        }
                    } else {
                        $failcount++;
                    }
                }

                if ($this->accept_regex($regex) && count($passeddata['tests'])) {
                    $this->passednormaltests [] = $passeddata;
                }
            }
        }
        if ($failcount == 0 && empty($exceptiontests) && $passcount > 0) {
            echo "\n\nWow! All tests passed!\n\n";
        }
        echo "======================\n";
        echo 'PASSED:     ' . $passcount . "\n";
        echo 'FAILED:     ' . $failcount . "\n";
        echo 'SKIPPED:    ' . $skipcount . "\n";
        echo "======================\n";
        if (!empty($slowbuildtests)) {
            echo "tests with slow matcher building:\n";
            echo implode("\n", $slowbuildtests) . "\n";
            echo "======================\n";
        }
        if (!empty($slowmatchtests)) {
            echo "tests with slow matching:\n";
            echo implode("\n", $slowmatchtests) . "\n";
            echo "======================\n";
        }
        if (!empty($exceptiontests)) {
            echo "tests with unhandled exceptions:\n";
            echo implode("\n", $exceptiontests) . "\n";
            echo "======================\n";
        }
    }

    public function test() {
        $this->run_normal_tests();
        //$serializabletests = __DIR__ . '/fuzzytests.txt';
        //$this->unserialize_test_data($serializabletests);
        mt_srand(100);
        $this->run_fuzzy_tests();
    }
}
