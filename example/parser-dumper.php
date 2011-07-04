#!/usr/bin/env php
<?php

ini_set('display_errors', 'stderr');
error_reporting(E_ALL | E_STRICT);

$file = dirname(dirname(__FILE__));

require $file . '/class/Patchwork/PHP/Parser.php';
require $file . '/class/Patchwork/PHP/Parser/Dumper.php';
require $file . '/class/Patchwork/PHP/Parser/Normalizer.php';
require $file . '/class/Patchwork/PHP/Parser/StringInfo.php';
require $file . '/class/Patchwork/PHP/Parser/NamespaceInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ScopeInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ClassInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ConstantInliner.php';
require $file . '/class/Patchwork/PHP/Parser/Scream.php';


$file = empty($argv[1])
    ? die("Please specify a PHP file as first argument\n")
    : $argv[1];

file_exists($file) || die("File not found: {$file}\n");


$parser = new Patchwork_PHP_Parser;
new Patchwork_PHP_Parser_Dumper($parser);
new Patchwork_PHP_Parser_Normalizer($parser);
new Patchwork_PHP_Parser_StringInfo($parser);
new Patchwork_PHP_Parser_NamespaceInfo($parser);
new Patchwork_PHP_Parser_ScopeInfo($parser);
new Patchwork_PHP_Parser_ClassInfo($parser);
new Patchwork_PHP_Parser_ConstantInliner($parser, realpath($file), array());
new Patchwork_PHP_Parser_Scream($parser);


$code = file_get_contents($file);
$code = $parser->parse($code);

if ($errors = $parser->getErrors())
{
    echo "\n--- Parser reported errors ---\n\n";

    foreach ($errors as $e)
    {
        echo "Line {$e[1]}: {$e[0]}\n";
    }
}