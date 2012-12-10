<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

class Patchwork_PHP_Preprocessor extends Patchwork_AbstractStreamProcessor
{
    protected

    $parserPrefix = 'Patchwork_PHP_Parser_',
    $namespaceRemoverCallback = 'Patchwork_PHP_Shim_Php530::add',
    $toStringCatcherCallback = 'Patchwork\ErrorHandler::handleToStringException',
    $closureShimParser,
    $constants = array(),
    $parsers = array(
        'PhpPreprocessor'    => true,
        'Normalizer'         => true,
        'ShortOpenEcho'      => -50400, // Load this only before 5.4.0
        'BracketWatcher'     => true,
        'ShortArray'         => -50400,
        'BinaryNumber'       => -50400,
        'StringInfo'         => true,
        'DocCommentInfo'     => true,
        'WorkaroundBug55156' => -50308,
        'Backport54Tokens'   => -50400,
        'Backport53Tokens'   => -50300,
        'NamespaceBracketer' => +50300, // Load this only for 5.3.0 and up
        'NamespaceInfo'      => true,
        'ScopeInfo'          => true,
        'ToStringCatcher'    => true,
        'DestructorCatcher'  => true,
        'ConstFuncDisabler'  => true,
        'ConstFuncResolver'  => true,
        'NamespaceResolver'  => -50300,
        'ConstantInliner'    => true,
        'ClassInfo'          => true,
        'NamespaceRemover'   => -50300,
        'InvokeShim'         => -50300,
        'ClosureShim'        => true,
        'ConstantExpression' => true,
        'FunctionShim'       => true,
        'StaticState'        => true,
    );

    protected static $code, $self;


    static function register($filter = null, $class = null)
    {
        if (empty($filter)) $filter = new self;
        return parent::register($filter, $class);
    }

    function __construct()
    {
        foreach ($this->parsers as $class => &$enabled)
            $enabled = $enabled
                && (0 > $enabled ? PHP_VERSION_ID < -$enabled : PHP_VERSION_ID >= $enabled)
                && class_exists($this->parserPrefix . $class);
    }

    function process($code)
    {
        self::$code = $code;
        self::$self = $this;
        return '<?php return eval(' . get_class($this) . '::selfProcess(__FILE__));';
    }

    static function selfProcess($uri)
    {
        $c = self::$code;
        $p = self::$self;
        $p->uri = $uri;
        self::$code = self::$self = null;
        return '?>' . $p->doProcess($c);
    }

    function doProcess($code)
    {
        foreach ($this->parsers as $class => $enabled)
            if ($enabled && !$this->buildParser($parser, $class))
                break;

        if (isset($parser))
        {
            $code = $parser->parse($code);

            if (isset($this->closureShimParser))
            {
                $code = $this->closureShimParser->finalizeClosures($code);
                $this->closureShimParser = null;
            }

            foreach ($parser->getErrors() as $e)
                $this->handleError($e);
        }

        return $code;
    }

    protected function buildParser(&$parser, $class)
    {
        if (!class_exists($c = $this->parserPrefix . $class)) return false;

        switch ($class)
        {
        case 'Backport54Tokens':
        case 'ShortOpenEcho':
        case 'BinaryNumber':
        case 'StaticState':
        case 'Normalizer':  $parser = new $c($parser); break;
        case 'PhpPreprocessor':  $p = new $c($parser, $this->filterPrefix); break;
        case 'ConstantInliner':  $p = new $c($parser, $this->uri, $this->constants); break;
        case 'ToStringCatcher':  $p = new $c($parser, $this->toStringCatcherCallback); break;
        case 'NamespaceRemover': $p = new $c($parser, $this->namespaceRemoverCallback); break;
        case 'ClosureShim':      $p = $this->closureShimParser = new $c($parser); break;
        default:                 $p = new $c($parser); break;
        }

        isset($parser) or $parser = $p;

        return true;
    }

    protected function handleError($e)
    {
        switch ($e['type'])
        {
        case 0: continue 2;
        case E_USER_NOTICE:
        case E_USER_WARNING:
        case E_USER_DEPRECATED: break;
        default:
        case E_ERROR: $e['type'] = E_USER_ERROR; break;
        case E_NOTICE: $e['type'] = E_USER_NOTICE; break;
        case E_WARNING: $e['type'] = E_USER_WARNING; break;
        case E_DEPRECATED: $e['type'] = E_USER_DEPRECATED; break;
        }

        user_error("{$e['message']} in {$this->uri} on line {$e['line']} as parsed by {$e['parser']}", $e['type']);
    }
}