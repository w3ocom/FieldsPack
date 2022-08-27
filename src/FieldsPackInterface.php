<?php

namespace w3ocom\FieldsPack;

interface FieldsPackInterface
{
    public function pack(array $arr): Result\Any;
    public function unpack(string $_raw): Result\Any;

    public function setFields(string $fields_un, ?string $last_field): Result\Any;

    public static function unpackFmtParse(string $fields_un): Result\Any;
    public static function packFmtFields(array $fields_arr): Result\Any;
    
    public static function fmtMultiBytesLen(string $fmtChar): int;
}
