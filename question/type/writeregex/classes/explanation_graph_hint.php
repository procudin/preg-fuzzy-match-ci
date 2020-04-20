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

namespace qtype_writeregex;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/preg/authoring_tools/preg_explaining_graph_tool.php');

/**
 * Class for writeregex explanation graph hint.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class explanation_graph_hint extends hint {

    /**
     * Get hint title.
     * @return string Hint title.
     */
    public function hint_title() {
        return get_string('explgraphhinttype', 'qtype_writeregex');
    }

    /**
     * @return string key for lang strings and field names
     */
    public function short_key() {
        return 'explgraph';
    }

    /**
     * @return qtype_preg_authoring_tool tool used for hint
     */
    public function tool($regex) {
        return new \qtype_preg_explaining_graph_tool($regex, $this->get_regex_options());
    }
}