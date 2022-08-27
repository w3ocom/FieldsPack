<?php
namespace w3ocom\FieldsPack\Result;

class Err implements Any {
    protected string $err = 'Undefined error';

    public function __construct(string $err = '') {
        if ($err) {
            $this->setErr($err);
        }
    }
    
    public function setErr(string $err): void {
        $this->err = $err;
    }

    public function getErr(): string {
        return $this->err;
    }

    public function getStr(): string {
        throw new \LogicException("Result is not string, it is error: " . $this->err);
    }

    public function getArr(): array {
        throw new \LogicException("Result is not array, it is error: " . $this->err);
    }
    
    public function isErr(): bool {
        return true;
    }

    public function isArr(): bool {
        return false;
    }

    public function __toString(): string {
        return $this->err;
    }
}
