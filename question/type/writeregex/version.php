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
 * Write regex question type version information.
 *
 * @package qtype
 * @subpackage writeregex
 * @copyright  2014 onwards Oleg Sychev, Volgograd State Technical University.
 * @author Mikhail Navrotskiy <m.navrotskiy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_writeregex';
$plugin->version   = 2015121700;

$plugin->requires  = 2013050100;

$plugin->maturity  = MATURITY_STABLE;

$plugin->dependencies = array(
    'qtype_shortanswer' => 2013050100,
    'qtype_preg' => 2013011800,
    'qtype_poasquestion' => 2013011800,
    'qbehaviour_adaptivehints' => 2013052500,
    'qbehaviour_adaptivehintsnopenalties' => 2013052500,
    'qbehaviour_interactivehints' => 2013060200
);
