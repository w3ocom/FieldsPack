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
            ]
        );
    }

    public function numPkDataProvider($param) {
        return [
            'Good1' => [1, pack('Cn', 1, 0), ['fC' => 1, 'fn' => 0]],
            'Good5' => [5, pack('CCC', 3, 1, 1), ['f0' => 1, 'f1' => 1]],
            'Bad6' => [6, false, 0],
            'Bad10' => [10, null, 0],
        ];
    }
     
    /*
     * @covers w3ocom\FieldsPack\FieldsPackRouter::__construct
     */
    public function testConstruct() {
        $obj = new FieldsPackRouter([]);
        $this->assertIsObject($obj);
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

}
