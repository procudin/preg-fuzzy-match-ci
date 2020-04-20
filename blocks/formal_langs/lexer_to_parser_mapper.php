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
/**
 * Defines abstract mapper from lexer to parser, which maps lexer tokens to parser AST
 *
 * @package    blocks
 * @subpackage formal_langs
 * @copyright &copy; 2011 Oleg Sychev, Volgograd State Technical University
 * @author     Oleg Sychev, Mamontov Dmitriy, Maria Birukova
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
global $CFG;

/**
 * Class block_formal_langs_lexer_to_parser_mapper
 *
 * Defines a mapper, which maps lexer tokens, generated by JLexPHP to PHP_ParserGenerators tokens
 */
abstract class block_formal_langs_lexer_to_parser_mapper {
    /**
     * A stack as a collection of frames with types and other conflict-solving data
     */
    protected $stack;
    /**
     * Constructs a mapper with mapped lexer and parser
     */
    public function __construct()
    {
        $this->push_stack_frame();
    }
    /**
     * Pushes a stack frame to a mapper
     */
    public function push_stack_frame()
    {
        $this->stack[] = array();
    }
    /**
     * Pops stack frame from mappers stacks list
     */
    public function pop_stack_frame()
    {
        unset($this->stack[count($this->stack) - 1]);
    }
    /** Returns name of parser class
     *  @return name of parser class
     */
    public abstract function parsername();
    /** Returns mappings of lexer tokens to parser tokens
     *  @param string $any name for any value matching
     *  @return array mapping
     */
    public abstract function maptable($any);
    /** Maps token from lexer to parser, returning name of constant to parser
     *  @param block_formal_langs_token_base  $token a token name
     *  @return string mapped token data
     */
    public function map($token) {
        $result = 0;
        $any = '===ANY===';
        $table = $this->maptable($any);
        if (array_key_exists($token->type(), $table)) {
            $maps = $table[$token->type()];
            $value = (string)$token->value();
            if (array_key_exists($value, $maps)) {
                $result = $maps[$value];
            } else {
                $result = $maps[$any];
            }
        }
        //echo $result . PHP_EOL;
        return $result;
    }

    /**
     * Creates new parser
     * @return block_formal_langs_parser_cpp_language|mixed
     */
    protected function make_parser() {
        $parsername = $this->parsername();
        // Note that this comment added to hint types for IDE.
        /** @var block_formal_langs_parser_cpp_language $parser */
        $parser = new $parsername();
        $parser->mapper = $this;
        $parser->repeatlookup = true;
        return $parser;
    }

    /** Parses new string for text parser
     *  @param block_formal_langs_processed_string $processedstring string, which has been tokenized before
     *  @param bool $iscorrect
     */
    public function parse($processedstring, $iscorrect)  {
        // TODO: What should we do with $iscorrect ?
        // Nullify stateful effects
        $oldstate = get_object_vars($this);
        if (count($processedstring->stream->errors) == 0)
        {
            $parser = $this->make_parser();
            $result = array();
            $tokens = $processedstring->stream->tokens;
            if (count($tokens))
            {
                $parser->currentid = count($tokens);
                $tokens = array_values( $tokens );
                $stuck = false;
                for($i = 0; $i < count($tokens); $i++) {
                    $token = $tokens[$i];
                    // Parse next token
                    $this->parse_token($token, $parser);

                    // Handle parsing error
                    if ($parser->error) {
                        $oldid = $parser->currentid;
                        $root   = $parser->root;
                        if ($stuck) {
                            $root []= $token;
                        } else {
                            --$i;
                            $stuck = true;
                        }
                        $parser = $this->make_parser();
                        $parser->currentid = $oldid;
                        if (count($result)) {
                            if (count($root)) {
                                $result = array_merge($result, $root);
                            }
                        } else {
                            $result = $root;
                        }
                    } else {
                        $stuck = false;
                    }
                }
                $parser->doParse(0, null);
                $root   = $parser->root;
                if (count($result)) {
                    $result = array_merge($result, $root);
                } else {
                    $result = $root;
                }
                $processedstring->set_syntax_tree($result);
            }
        }
        // Nullify stateful effects
        if (count($oldstate)) {
            foreach($oldstate as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    /** Returns major code for specified token
     *  @param block_formal_langs_token_base $token a token
     *  @return int constant data
     */
    public function major_code_for($token) {
        $parsername = $this->parsername();
        $major = $this->map($token);
        $constant = 0;
        if ($major != null) {
            $constant = $parsername . '::' . $major;
            // echo $constant . "\r\n";
            $constant = constant($constant);
        }
        return $constant;
    }
    /**
     * Makes parser parse specific token
     *  @param block_formal_langs_token_base $token parsed token
     *  @param mixed $parser a parser result
     */
    protected function parse_token($token, $parser) {
        $constant = $this->major_code_for($token);
        // Note that this comment added to hint types for IDE.
        /** @var block_formal_langs_parser_cpp_language $parser */
        $parser->doParse($constant, $token);
    }

}
