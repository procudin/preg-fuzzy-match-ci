<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/tests/cross_tester.php');

set_config('fa_transition_limit', 10000, 'qtype_preg');
set_config('fa_state_limit', 10000, 'qtype_preg');

class qtype_preg_fa_cross_tester extends qtype_preg_cross_tester {

    public function engine_name() {
        return 'fa_matcher';
    }
}
