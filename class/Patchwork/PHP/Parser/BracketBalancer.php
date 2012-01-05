<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

Patchwork_PHP_Parser::createToken('T_BRACKET_CLOSE');

/**
 * The BracketBalancer parser counts opening brackets and triggers callbacks on corresponding closing brackets.
 */
class Patchwork_PHP_Parser_BracketBalancer extends Patchwork_PHP_Parser
{
    protected

    $brackets = array(),
    $callbacks = array(
        '~pushBracket' => array('{', '[', '('),
        'popBracket'   => array('}', ']', ')'),
    );


    protected function pushBracket(&$token)
    {
        $b =& $this->brackets[];

        switch ($token[0])
        {
        case '{': $b = '}'; break;
        case '[': $b = ']'; break;
        case '(': $b = ')'; break;
        }

        if (empty($this->tokenRegistry[T_BRACKET_CLOSE])) return;

        $b = array($b, $this->tokenRegistry[T_BRACKET_CLOSE]);
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);
    }

    protected function popBracket(&$token)
    {
        $last = array_pop($this->brackets);

        if (empty($last) || $token[0] !== $last[0])
        {
            // Brackets are not correctly balanced, code has a parse error.
            $this->unregister($this->callbacks);
        }
        else if (isset($last[1]))
        {
            // Bracket has registered on-close callbacks
            $this->tokenRegistry[T_BRACKET_CLOSE] = $last[1];
            $this->register(array('~tagAfterClose' => T_BRACKET_CLOSE));
            return T_BRACKET_CLOSE;
        }
    }

    protected function tagAfterClose(&$token)
    {
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);
    }
}