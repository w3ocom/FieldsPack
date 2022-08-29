<?php

namespace Test\w3ocom\FieldsPack;

use w3ocom\FieldsPack\FieldsPackRouter;

class FieldsPackRouterTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var FieldsPackRouter
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void {
        $this->object = new FieldsPackRouter(
            [
                // Good format variants
                1 => 'CfC/nfn',
                5 => 'C_f/Cf0/Cf1',

                // Bad format variants
                6 => 'X_f',
                200 => 'Cf0/Cf1',
            ]
        );
    }

    /*
     * @covers w3ocom\FieldsPack\FieldsPackRouter::setFmtChar
     */
    public function testSetFmtChar() {
        $fmt_len = $this->object->setFmtChar('m');
        $this->assertEquals(3, $fmt_len);
        
        $fmt_len = $this->object->setFmtChar('X');
        $this->assertEquals(0, $fmt_len);
    }
     
    /*
     * @covers w3ocom\FieldsPack\FieldsPackRouter::__construct
     */
    public function testConstruct() {
        $obj1 = new FieldsPackRouter([1 => 'Cx/Cy']);
        $this->assertIsObject($obj1);
        
        $obj2 = new FieldsPackRouter([2 => 'Nx/Ny'], NULL, $obj1);
        $this->assertIsObject($obj2);
        
        $un = $obj2->unpackByPrefix(chr(1) . chr(2) . chr(3));
        $this->assertFalse($un->isErr());
        $this->assertEquals([
            'x' => 2,
            'y' => 3,
            '*' => ''
        ], $un->getArr());
    }
    
    
    public function numPkDataProvider($param) {
        return [
            'Good1' => [1, pack('Cn', 1, 0), ['fC' => 1, 'fn' => 0]],
            'Good5' => [5, pack('CCC', 3, 1, 1), ['f0' => 1, 'f1' => 1]],
            'Bad6' => [6, false, 0],
            'Bad10' => [10, null, 0],
        ];
    }
    /**
     * @dataProvider numPkDataProvider
     * @covers w3ocom\FieldsPack\FieldsPackRouter::getFieldsPackByNum
     */
    public function testGetFieldsPackByNum($num, $expected_pk, $from_arr) {
        if ($expected_pk === false) {
            $this->expectException(\Exception::class);
        }

        $fpk_obj = $this->object->GetFieldsPackByNum($num);

        if ($expected_pk) {
            $this->assertIsObject($fpk_obj);
            $pk = $fpk_obj->pack($from_arr);
            $this->assertFalse($pk->isErr());
            $pk = $pk->getStr();
            $this->assertEquals($expected_pk, $pk);
        } else {
            $this->assertNull($fpk_obj);
        }
    }
    

    public function unpackByPrefixProvider() {
        return [
            'Test1' => [
                'C',1,
                chr(1) . pack('Cn', 1, 0), //data
                ['fC' => 1, 'fn' => 0, '*' => '']  //expected arr
            ],
            'Test1again' => [
                'C',1,
                // run test1 again
                chr(1) . pack('Cn', 1, 0), //data
                ['fC' => 1, 'fn' => 0, '*' => '']  //expected arr
            ],

            'Test3bytesPrefix' => [
                'm',3,
                chr(128). chr(0) . chr(200) . chr(123) . chr(111), //data
                ['f0' => 123, 'f1' => 111, '*' => '']
            ],
            
            'Test2bytesPrefix' => [
                'n',2,
                chr(0) . chr(200) . chr(123) . chr(111), //data
                ['f0' => 123, 'f1' => 111, '*' => ''],
            ],
            
            'NotFoundPrefix' => [
                'C',1,
                chr(123). 'xxx',
                NULL
            ],
        ];
    }
    /**
     * @dataProvider unpackByPrefixProvider
     * @covers w3ocom\FieldsPack\FieldsPackRouter::unpackByPrefix
     */
    public function testUnpackByPrefix(
        $prefix_fmt_char,
        $prefix_fmt_len,
        $from_data,
        $expected_arr
    ) {
        $fmt_len = $this->object->setFmtChar($prefix_fmt_char);
        $this->assertEquals($fmt_len, $prefix_fmt_len);

        $result = $this->object->unpackByPrefix($from_data);
        if (is_array($expected_arr)) {
            $this->assertIsObject($result);
            $this->assertFalse($result->isErr());
            $arr = $result->getArr();
            $this->assertEquals($expected_arr, $arr);
        } else {
            $this->assertNull($result);
        }
    }
    
    public function packByPrefixProvider() {
        return [
            'testm' => [
                'm',3,
                200,
                ['f0' => 1, 'f1'=>2],
                chr(128) . chr(0) . chr(200) . chr(1) . chr(2),
            ],
            'test1C' => [
                'C',1,
                1, //fmtNum   1 => 'CfC/nfn',
                ['fC'=>123, 'fn' => 1],
                chr(1) . chr (123) . chr(0) . chr(1)
            ],
            'test1n' => [
                'n',2,
                1, //fmtNum
                ['fC'=>123, 'fn' => 1],
                chr(0) . chr(1) . chr (123) . chr(0) . chr(1)
            ],
            'test1N' => [
                'N', 4,
                1, //fmtNum
                ['fC'=>123, 'fn' => 1],
                chr(0). chr(0) . chr(0) . chr(1) . chr (123) . chr(0) . chr(1)
            ],
            'testCOutOfRange' => [
                'C', 1,
                200,
                ['fC'=>123, 'fn' => 1],
                false,
            ],
            'testmOutOfRange' => [
                'm',3,
                1,
                ['fC'=>123, 'fn' => 1],
                false,
            ],

            'test2undefined' => [
                'C',1,
                2, //fmtNum (undefined #2)
                ['fC' => 123, 'fn' => 1],
                NULL, // expecting error
            ],
        ];
    }
    
    /**
     * @dataProvider packByPrefixProvider
     * @covers w3ocom\FieldsPack\FieldsPackRouter::packByPrefix
     */
    public function testPackByPrefix(
        $prefix_fmt_char,
        $prefix_fmt_len,
        $fmt_num,
        $from_arr,
        $expected_data
    ) {
        $fmt_len = $this->object->setFmtChar($prefix_fmt_char);
        $this->assertEquals($fmt_len, $prefix_fmt_len);

        $result = $this->object->packByPrefix($fmt_num, $from_arr);
        if (is_string($expected_data)) {
            $this->assertFalse($result->isErr());
            $pk = $result->getStr();
            $this->assertEquals($expected_data, $pk);
        } elseif (is_null($expected_data)) {
            $this->assertNull($result);
        } else {
            $this->assertTrue($result->isErr());
        }
    }
    
    public function testSetNextRouter() {
        $next_router_obj = new FieldsPackRouter([
            // create next router with format #250, m-prefix (3-bytes)
            250 => 'C_f/Cf1/Cf2'
        ], 'm');
        // appent next router to current router
        $this->object->setNextRouter($next_router_obj);
        
        // try packing format #250 by current router
        // expected behavior: forwarding the packByPrefix to the next_router_obj
        $result = $this->object->packByPrefix(250, ['f2' => 123]);
        $this->assertFalse($result->isErr());

        // if packed successful, expected string is:
        $this->assertEquals(
            chr(128) . chr(0) . chr(250). // m-prefix pack
                chr(2) . chr(123), // fieldsOpt pack
            $result->getStr()
        );
    }
}
