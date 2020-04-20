<?php
function parse_1d_array($text, $name, &$tables, $public = false)
{
    if (!$public) {
        $regexp = "/static[ ]+(\\\$$name)[ ]*= [ ]*array\\(([^)]+)\\);/m";
    } else {
        $regexp = "/static[ ]+public[ ]+(\\\$$name)[ ]*= [ ]*array\\(([^)]+)\\);/m";
    }
    $matches = array();
    preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches))
    {
        $valuesasstring = str_replace(array("\r", "\n"), array("", ""), $matches[2][0]);
        if (!$public) {
            $replacetext = "static \$$name = null;";
        } else {
            $replacetext = "static public \$$name = null;";
        }
        $text = str_replace($matches[0][0], $replacetext, $text);
        $list = array_map('trim', explode(',', $valuesasstring));
        $tables['$' . $name] = $list;
    }
    return $text;
}

function parse_2d_array($text, $name, &$tables, $public = false)
{
    $array_list_regexp = "(([\r\n \t]|.)*)";
    $matches = array();
    if (!$public) {
        $regexp = "/static[ ]+(\\\$$name)[ ]*= [ ]*([^;]+);/m";
    } else {
        $regexp = "/static[ ]+public[ ]+(\\\$$name)[ ]*= [ ]*([^;]+);/m";
    }
    preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches))
    {
        if (!$public) {
            $replacetext = "static \$$name = null;";
        } else {
            $replacetext = "static public \$$name = null;";
        }
        $text = str_replace($matches[0][0], $replacetext, $text);
        $replaces = preg_replace(
            array("/^array\\(/", "/\\)\$/m"),
            array("", ""),
            $matches[2][0]
        );
        $newmatches = array();
        preg_match_all("/array\\(([^)]*)\\)/", $replaces, $newmatches);
        $list = array();
        foreach ($newmatches[1] as $txt) {
            $txt = str_replace(array("\r", "\n"), array("", ""), $txt);
            $list[] = array_filter(array_map('trim', explode(',', $txt)), function($o) { return strlen($o) != 0; });
        }
        $tables['$' . $name] = $list;
    }
    return $text;
}
function transform_scaner($input, $output)
{
    $text = file_get_contents($input);
    $tables  = array();
    $text = parse_1d_array($text, 'yy_state_dtrans', $tables);
    $text = parse_1d_array($text, 'yy_acpt', $tables);
    $text = parse_1d_array($text, 'yy_cmap', $tables);
    $text = parse_1d_array($text, 'yy_map', $tables);
    $text = parse_1d_array($text, 'yy_rmap', $tables);
    $tables2d = array();
    $text = parse_2d_array($text, "yy_nxt", $tables2d);
    $lexerraw =  strpos($text, "lexer_raw");
    $constructoffset = strpos($text, "__construct", $lexerraw + 1);
    $closingbraceoffset = strpos($text, "}", $constructoffset);
    $replace = "	if (self::\$yy_state_dtrans == null) { self::initialize_tables(); }\r\n\t}\r\n\r\n";
    $replace .= "\tpublic static function initialize_tables() {\r\n";
    foreach($tables as $key => $value) {
        if (count($value) < 32767) {
            $replace .= "\t\tself::$key = array(" . implode(",", $value) . ");\r\n";
        } else {
            $value = array_chunk($value, 32767);
            $value = array_map(function($o) { return "array(" . implode(", ", $o) . ")"; }, $value);
            $replace .=  "\t\tself::$key = array_merge(" . implode(",", $value) . ");\r\n";
        }
    }
    foreach($tables2d as $key => $value) {
        $myvalues = array_map(function($o)  {
            if (count($o) < 32767) {
                return "array(" . implode(",", $o) . ")";
            } else {
                $chunks = array_chunk($o, 32767);
                $chunks = array_map(function($o2) { return "array(" . implode(", ", $o2) . ")"; }, $chunks);
                return "array_merge(" . implode(",", $chunks) . ")";
            }
        }, $value);
        $replace .= "\t\tself::$key = array();\r\n";
        foreach($myvalues as $myvalue) {
            $replace .= "\t\tself::{$key}[] = $myvalue;\r\n";
        }
    }
    $replace .= "\t}\r\n";
    $text = substr_replace($text, $replace, $closingbraceoffset, 1);
    file_put_contents($output, $text);
}

function transform_parser($input, $output)
{
    $text = file_get_contents($input);
    $tables  = array();
    $text = parse_1d_array($text, 'yy_action', $tables, true);
    $text = parse_1d_array($text, 'yy_lookahead', $tables, true);
    $text = parse_1d_array($text, 'yy_reduce_ofst', $tables, true);
    $text = parse_1d_array($text, 'yy_shift_ofst', $tables, true);
    $text = parse_1d_array($text, 'yy_default', $tables, true);
    $text = parse_1d_array($text, 'yyTokenName', $tables, true);
    $text = parse_1d_array($text, 'yyRuleName', $tables, true);

    $tables2d = array();
    $text = parse_2d_array($text, "yyExpectedTokens", $tables2d, true);

    $startclassoffset =  strpos($text, "language#line");
    $openingbracket = strpos($text, "{", $startclassoffset + 1);
    $replace .= "\r\n\r\n\tpublic function __construct()\r\n\t{\r\n";
    $replace .= "\t\tif (self::\$yy_action == null) {\r\n";
    foreach($tables as $key => $value) {
        if (count($value) < 32767) {
            $replace .= "\t\tself::$key = array(" . implode(",", $value) . ");\r\n";
        } else {
            $value = array_chunk($value, 32767);
            $value = array_map(function($o) { return "array(" . implode(", ", $o) . ")"; }, $value);
            $replace .=  "\t\tself::$key = array_merge(" . implode(",", $value) . ");\r\n";
        }
    }
    foreach($tables2d as $key => $value) {
        $myvalues = array_map(function($o)  {
            if (count($o) < 32767) {
                return "array(" . implode(",", $o) . ")";
            } else {
                $chunks = array_chunk($o, 32767);
                $chunks = array_map(function($o2) { return "array(" . implode(", ", $o2) . ")"; }, $chunks);
                return "array_merge(" . implode(",", $chunks) . ")";
            }
        }, $value);
        $replace .= "\t\tself::$key = array();\r\n";
        foreach($myvalues as $myvalue) {
            $replace .= "\t\tself::{$key}[] = $myvalue;\r\n";
        }
    }
    $replace .= "\t\t}\r\n";
    $replace .= "\t}\r\n";
    $text = substr_replace($text, $replace, $openingbracket + 1, 0);
    file_put_contents($output, $text);
}

$valid = false;
if (count($argv) == 4) {
    if ($argv[1] == "-scanner")  {
        transform_scaner($argv[2], $argv[3]);
        $valid = true;
    }
    if ($argv[1] == "-parser")  {
        transform_parser($argv[2], $argv[3]);
        $valid = true;
    }
}
if (!$valid) {
    echo "Code transformer for solving PHP5.6 bug (https://bugs.php.net/bug.php?id=68057)\n";
    echo "php php56ize.pgp [-scanner|parser] input output\n";
}
