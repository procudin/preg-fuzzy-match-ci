<?php
// This file is part of Formal Languages block - https://bitbucket.org/oasychev/moodle-plugins/
//
// Formal Languages block is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Formal Languages block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Formal Languages block.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/formal_langs/block_formal_langs.php');

function xmldb_block_formal_langs_upgrade($oldversion = 0) {
    global $CFG, $DB;

    if ($oldversion < 2013030700) {
        $lang = new stdClass();
        $lang->uiname = 'C++ programming language';
        $lang->description = 'C++ language, with only lexer';
        $lang->name = 'cpp_language';
        $lang->scanrules = null;
        $lang->parserules = null;
        $lang->version='1.0';
        $lang->visible = 1;

        $DB->insert_record('block_formal_langs',$lang);
    }

    if ($oldversion < 2013041400) {
        $lang = new stdClass();
        $lang->uiname = 'C formatting string rules';
        $lang->description = 'C formatting string rules, as used in printf';
        $lang->name = 'printf_language';
        $lang->scanrules = null;
        $lang->parserules = null;
        $lang->version='1.0';
        $lang->visible = 1;

        $DB->insert_record('block_formal_langs',$lang);
    }

    if ($oldversion < 2013071900) {
        $dbman = $DB->get_manager();
        $bfl = new xmldb_table('block_formal_langs');
        $lexemenamefield = new xmldb_field('lexemname', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'visible');
        $dbman->add_field($bfl, $lexemenamefield);
    }

    if ($oldversion < 2013091800) {
        $dbman = $DB->get_manager();
        $bfl = new xmldb_table('block_formal_langs');
        $uinamefield = new xmldb_field('ui_name', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'id');
        $dbman->rename_field($bfl, $uinamefield, 'uiname');
    }

    if ($oldversion < 2013091817) {
        $dbman = $DB->get_manager();
        $perms = new xmldb_table('block_formal_langs_perms');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $perms->addField($field);

        $field = new xmldb_field('languageid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $perms->addField($field);

        $field = new xmldb_field('contextid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $perms->addField($field);

        $field = new xmldb_field('visible');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, null);
        $perms->addField($field);

        $idpk = new xmldb_key('primary');
        $idpk->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);
        $perms->addKey($idpk);

        $dbman->create_table($perms);
    }

    if ($oldversion < 2013091818) {
        block_formal_langs::sync_contexts_with_config();
    }

    if ($oldversion < 2013111018)  {
        block_formal_langs::sync_contexts_with_config();
    }

    if ($oldversion < 2013120600) {
        $dbman = $DB->get_manager();
        $bfl = new xmldb_table('block_formal_langs');
        // Rename old buggy update, if somebody applied it
        if ($dbman->field_exists($bfl, 'lexemename')) {
            $lexemenamefield = new xmldb_field('lexemename', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'visible');
            $dbman->rename_field($bfl, $lexemenamefield, 'lexemname');
        }
        $field = new xmldb_field('author');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'lexemname');
        $dbman->add_field($bfl, $field);
    }

    if ($oldversion < 2014060500) {
        /*
		$lang = new stdClass();
        $lang->uiname = 'C++ parseable programming language';
        $lang->description = 'C++ parseable language';
        $lang->name = 'cpp_parseable_language';
        $lang->scanrules = null;
        $lang->parserules = null;
        $lang->version='1.0';
        $lang->visible = 1;
        $lang->lexemname = '';
        $lang->version='1.0';
        $lang->visible = 1;

        $DB->insert_record('block_formal_langs',$lang);
		*/
    }

    if ($oldversion < 2015050600)  {
        $dbman = $DB->get_manager();
        $langname = 'cpp_parseable_language';
        $clause = $DB->sql_compare_text('name')  . ' =  ?';
        $statement = 'SELECT * FROM {block_formal_langs} WHERE ' . $clause;
        $parseablelang = $DB->get_record_sql($statement, array($langname));
        $langname = 'cpp_language';
        $cpplang = $DB->get_record_sql($statement, array($langname));
        if ($parseablelang !== false && $dbman->table_exists('qtype_correctwriting')) {
            $dependentquestions = $DB->get_records('qtype_correctwriting', array('langid' => $parseablelang->id));
            if (count($dependentquestions)) {
                foreach($dependentquestions as $id => $qobj) {
                    $qobj->langid = $cpplang->id;
                    $DB->update_record('qtype_correctwriting', $qobj);
                }
            }
        }
        if ($cpplang !== false) {
            $cpplang->name = 'cpp_parseable_language';
            $cpplang->description = 'C++ language with basic preprocessor support';
            $DB->update_record('block_formal_langs', $cpplang);
        }
    }
    // Fix for duplicate languages
    if ($oldversion < 2015100101) {
        $dbman = $DB->get_manager();
        $cppparselang = 'cpp_parseable_language';
        $cpplang = 'cpp_language';
        $clause = $DB->sql_compare_text('name')  . ' =  ?';
        $statement = 'SELECT * FROM {block_formal_langs} WHERE ' . $clause;
        $parseablelang = $DB->get_record_sql($statement, array($cppparselang));
        $cpplangrecord = $DB->get_record_sql($statement, array($cpplang));
        if ($cpplangrecord != false && $parseablelang != false) {
            if ($dbman->table_exists('qtype_correctwriting')) {
                $DB->execute('UPDATE {qtype_correctwriting} '
                          . 'SET `langid` = \'' . $parseablelang->id . '\' '
                          . 'WHERE `langid` = \'' . $cpplangrecord->id . '\'');
            }
            if ($dbman->table_exists('qtype_preg_options')) {
                $DB->execute('UPDATE {qtype_preg_options} '
                          . 'SET `langid` = \'' . $parseablelang->id . '\' '
                          . 'WHERE `langid` = \'' . $cpplangrecord->id . '\'');
            }
            $DB->delete_records('block_formal_langs_perms', array('languageid' => $cpplangrecord->id));
            $DB->delete_records('block_formal_langs', array('id' => $cpplangrecord->id));
        }
    }


    if ($oldversion < 2015102400) {
        $dbman = $DB->get_manager();

        // Make word forms table
        $wordforms = new xmldb_table('block_formal_langs_word_frms');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $wordforms->addField($field);

        $field = new xmldb_field('language', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'id');
        $wordforms->addField($field);

        $field = new xmldb_field('formname', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'language');
        $wordforms->addField($field);

        $field = new xmldb_field('originalform', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'formname');
        $wordforms->addField($field);

        $field = new xmldb_field('form', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'originalform');
        $wordforms->addField($field);

        $idpk = new xmldb_key('primary');
        $idpk->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);
        $wordforms->addKey($idpk);

        $dbman->create_table($wordforms);

        // Make description forms, that will be substituted into descriptions
        $descriptionparts = new xmldb_table('block_formal_langs_dsc_parts');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $descriptionparts->addField($field);

        $field = new xmldb_field('tablename', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'id');
        $descriptionparts->addField($field);

        $field = new xmldb_field('tableid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'tablename');
        $descriptionparts->addField($field);

        $field = new xmldb_field('number');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'tableid');
        $descriptionparts->addField($field);

        $field = new xmldb_field('position');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'number');
        $descriptionparts->addField($field);

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT ,null,null,null, null, null, 'position');
        $descriptionparts->addField($field);

        $idpk = new xmldb_key('primary');
        $idpk->set_attributes(XMLDB_KEY_PRIMARY, array('id'), null, null);
        $descriptionparts->addKey($idpk);

        $dbman->create_table($descriptionparts);
    }

    // Move configs from $CFG to config_plugins table.
    if ($oldversion < 2016102100) {
        $DB->execute(
            "DELETE FROM {config_log} WHERE " . $DB->sql_like('name', ':name'),
            ['name' => 'block_formal_langs%']
        );

        $configs = $DB->get_records_sql(
            "SELECT * from {config} WHERE " . $DB->sql_like('name', ':name'),
            ['name' => 'block_formal_langs%']
        );

        $newconfigs = array_map(function ($config) {
            return (object)[
                'plugin' => 'block_formal_langs',
                'name' => str_replace('block_formal_langs_', '', $config->name),
                'value' => $config->value,
            ];
        }, $configs);

        $DB->insert_records('config_plugins', $newconfigs);

        $DB->execute(
            "DELETE FROM {config} WHERE " . $DB->sql_like('name', ':name'),
            ['name' => 'block_formal_langs%']
        );
    }

    return true;
}
