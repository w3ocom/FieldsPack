<?php
namespace w3ocom\FieldsPack\Result;

class Str implements Any {
    protected string $data;
    
    public function __construct(string $str) {
        $this->setStr($str);
    }
    
    public function setStr($str): void {
        $this->data = $str;
    }

    public function getArr(): array {
        throw new LogicException("Result is string, not array");
    }
    
    public function isErr(): bool {
        return false;
    }
    
    public function isArr(): bool {
        return false;
    }

    public function getErr(): string {
        return '';
    }
    
    public function getStr(): string {
        return $this->data;
    }
}
