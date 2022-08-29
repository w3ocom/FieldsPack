<?php

namespace w3ocom\FieldsPack;

class FieldsPackRouter {
    public array $fields_un_arr; // array of string

    protected array $fpk_obj_arr = [];   // array of FieldsPack object

    public function __construct(array $fields_un_arr) {
        $this->fields_un_arr = $fields_un_arr;
    }
    
    public function getFieldsPackByNum(int $num): ?FieldsPackInterface {
        if (!isset($this->fields_un_arr[$num])) {
            return NULL;
        }
        if (!isset($this->fpk_obj_arr[$num])) {
            $this->fpk_obj_arr[$num] = new FieldsPackOpt($this->fields_un_arr[$num]);
        }
        return $this->fpk_obj_arr[$num];
    }
}
