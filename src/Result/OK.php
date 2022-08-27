<?php
namespace w3ocom\FieldsPack\Result;

class OK implements Any {

    public function getArr(): array {
        return [];
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
        return '';
    }
}
