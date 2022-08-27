<?php
namespace w3ocom\FieldsPack\Result;

class Arr implements Any {
    protected array $data;
    
    public function __construct(array $arr) {
        $this->setArr($arr);
    }
    
    public function setArr(array $arr): void {
        $this->data = $arr;
    }

    public function getArr(): array {
        return $this->data;
    }
    
    public function isErr(): bool {
        return false;
    }

    public function isArr(): bool {
        return true;
    }


    public function getErr(): string {
        return '';
    }
    
    public function getStr(): string {
        throw new LogicException("Result is array");
    }
}
