<?php

namespace Test\w3ocom\FieldsPack;

use w3ocom\FieldsPack\FieldsPackOpt;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2022-08-26 at 18:16:27.
 */
class FieldsPackOptTest extends FieldsPackMainTest {
   
    protected $is_opt_mode = true;

    public $tst_class = 'FieldsPackOpt';

    /**
     * @dataProvider packUnpackProvider
     * @covers w3ocom\FieldsPack\FieldsPackOpt::pack
     */
    public function testPack($fields_un, $pk, $arr) {
        $this->object->setFields($fields_un);
        $result = $this->object->pack($arr);
        $this->assertFalse($result->isErr());
        $this->assertEquals($pk, $result->getStr());
    }
    
    /**
     * @dataProvider unpackBadProvider
     * @dataProvider packUnpackProvider
     * @covers w3ocom\FieldsPack\FieldsPackOpt::unpack
     */
    public function testUnpack($fields_un, $pk, $arr) {
        $this->object->setFields($fields_un);
        
        $result = $this->object->unpack($pk);

        if (is_array($arr)) {
            if (!isset($arr['*'])) {
                $arr['*'] = '';
            }
            $this->assertFalse($result->isErr());
            $this->assertEquals($arr, $result->getArr());
        } else {
            $this->assertTrue($result->isErr());
            $this->assertEquals($arr, $result->getErr());
        }
    }
    
    /*
     * @covers w3ocom\FieldsPack\FieldsPackOpt::unpack
     */
    public function testUnpackIncH() {
        $this->object->setFields('C_f/Cf2/Cf3');
        $result = $this->object->pack(['f2' => 2, 'f3' => 3]);
        $this->assertFalse($result->isErr());
        $pk = $result->getStr();       
        $this->assertEquals(chr(3) . chr(2) . chr(3), $pk);
        // set inc_h
        $this->object->inc_h = true;
        $result = $this->object->unpack($pk);
        $this->assertFalse($result->isErr());
        $un = $result->getArr();
        $this->assertArrayHasKey('_h', $un);
    }
    
    public function testPackBadFirstFieldName() {
        $result = $this->object->setFields("Cf1/Cf2");
        $this->assertFalse($result->isErr());
        $this->object->header_bytes = 1;
        $result = $this->object->pack(['f1' => 1, 'f2' => 2]);
        $this->assertTrue($result->isErr());
    }

    public function testPackUnknownField() {
        $result = $this->object->setFields("C_f/Cf1/Cf2");
        $this->assertFalse($result->isErr());
        $result = $this->object->pack(['f1' => 1, 'f3' => null]);        
        $this->assertTrue($result->isErr());
    }

    public function testUnpackIllegalRaw() {
        $obj = new FieldsPackOpt("n_f/Cf1");
        $result = $obj->unpack('a');
        $this->assertTrue($result->isErr());
    }

}