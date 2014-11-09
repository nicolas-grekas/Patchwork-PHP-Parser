<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

// PHP 5.3 tokens
defined('T_DIR') || Parser::createToken('T_DIR');
defined('T_GOTO') || Parser::createToken('T_GOTO');
defined('T_NS_C') || Parser::createToken('T_NS_C');
defined('T_NAMESPACE') || Parser::createToken('T_NAMESPACE');

// PHP 5.4 tokens
defined('T_TRAIT') || Parser::createToken('T_TRAIT');
defined('T_TRAIT_C') || Parser::createToken('T_TRAIT_C');
defined('T_CALLABLE') || Parser::createToken('T_CALLABLE');
defined('T_INSTEADOF') || Parser::createToken('T_INSTEADOF');

// PHP 5.5 tokens
defined('T_YIELD') || Parser::createToken('T_YIELD');
defined('T_FINALLY') || Parser::createToken('T_FINALLY');

// PHP 5.6 tokens
defined('T_POW_EQUAL') || Parser::createToken('T_POW_EQUAL');
defined('T_POW') || Parser::createToken('T_POW');
defined('T_ELLIPSIS') || Parser::createToken('T_ELLIPSIS');

// PHP 7 tokens
defined('T_COALESCE') || Parser::createToken('T_COALESCE');

/**
 * The BackportTokens parser backports tokens introduced since PHP 5.3
 *
 * @todo Backport nowdoc syntax, allow heredoc in static declarations.
 * @todo Work around https://bugs.php.net/60097
 */
class BackportTokens extends Parser
{
    protected $backports = array(
        // PHP 5.3 tokens
        'goto' => T_GOTO,
        '__dir__' => T_DIR,
        'namespace' => T_NAMESPACE,
        '__namespace__' => T_NS_C,

        // PHP 5.4 tokens
        'trait' => T_TRAIT,
        'callable' => T_CALLABLE,
        '__trait__' => T_TRAIT_C,
        'insteadof' => T_INSTEADOF,

        // PHP 5.5 tokens
        'yield' => T_YIELD,
        'finally' => T_FINALLY,
    );

    function __construct(parent $parent)
    {
        $b = $this->backports;

        foreach ($b as $k => $i)
            if (self::T_OFFSET >= $i)
                unset($b[$k]);

        parent::__construct($parent);

        $this->backports += $b;
    }

    protected function getTokens($code, $is_fragment)
    {
        $b = $this->backports;

        foreach ($b as $k => $i)
            if (false === stripos($code, $k))
                unset($b[$k]);

        $code = parent::getTokens($code, $is_fragment);
        $i = 0;

        if ($b)
        {
            while (isset($code[++$i]))
            {
                if (T_STRING === $code[$i][0] && isset($b[$k = strtolower($code[$i][1])]) && T_OBJECT_OPERATOR !== $code[$i-1][0])
                {
                    $code[$i][0] = $b[$k];
                }
                else if ('.' === $code[$i] && isset($code[$i+1], $code[$i+2]) && '.' === $code[$i+1] && '.' === $code[$i+2])
                {
                    $code[$i] = array(T_ELLIPSIS, '...');
                    $code[$i+1] = array(T_WHITESPACE, '');
                    $code[$i+2] = array(T_WHITESPACE, '');
                }
                else if ('*' === $code[$i] && isset($code[$i+1]))
                {
                    if ('*' === $code[$i+1])
                    {
                        $code[$i] = array(T_POW, '**');
                        $code[$i+1] = array(T_WHITESPACE, '');
                    }
                    else if (T_MUL_EQUAL === $code[$i+1][0])
                    {
                        $code[$i] = array(T_POW_EQUAL, '**=');
                        $code[$i+1] = array(T_WHITESPACE, '');
                    }
                }
                else if ('?' === $code[$i] && isset($code[$i+1]) && '?' === $code[$i+1])
                {
                    $code[$i] = array(T_COALESCE, '??');
                    $code[$i+1] = array(T_WHITESPACE, '');
                }
            }
        }

        return $code;
    }
}
