<?php

namespace Patchwork\Tests\PHP;

use Patchwork\PHP\Parser;

class BinaryNumberTest extends \PHPUnit_Framework_TestCase
{
    protected function getParser($dump = false)
    {
        $p = $dump ? new Parser\Dumper : new Parser;

        $p->targetPhpVersionId = 50300;

        $p = new Parser\BinaryNumber($p);

        return $p;
    }

    function testParse()
    {
        $parser = $this->getParser();

        $in = <<<EOPHP
<?php
0b01010101010;
0B01010101010;
EOPHP;

        $out = <<<EOPHP
<?php
0x2aa;
0x2aa;
EOPHP;

        $this->assertSame( $out, $parser->parse($in) );
        $this->assertSame( array(), $parser->getErrors() );
    }

    function testBigNumbers()
    {
        $parser = $this->getParser(true);

        $in = <<<EOPHP
<?php
0b01010101010;
0b1111111111111111111111111111111111111111111111111111111111111111;
EOPHP;

        ob_start();
        echo $parser->parse($in);

        $out = <<<EOTXT
Line                    Source code Parsed code                    Token type(s)
=================================================================================
   1                         <?php⏎                                T_OPEN_TAG
   2                  0b01010101010 0x2aa                          T_LNUMBER
   3 0b111111111111111111111111111… 0x10000000000000000            T_DNUMBER
   3                              ;                                ;

EOTXT;

        $this->assertSame( $out, ob_get_clean() );
        $this->assertSame( array(), $parser->getErrors() );
    }
}
