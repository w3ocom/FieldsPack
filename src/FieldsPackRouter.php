<?php
declare(strict_types=1);

namespace w3ocom\FieldsPack;

class FieldsPackRouter {
    /**
     * strings-array for all formats defined in this router
     * @var array<string>
     */
    public array $fields_un_arr;

    /**
     * created FieldsPack-objects
     * @var array<FieldsPackOpt>
     */
    protected array $fpk_obj_arr = [];   // array of FieldsPack object
    
    /**
     * if the object is not null, the packByPrefix and unpackByPrefix functions will be sent to it
     * @var object|null
     */
    private ?object $next_router_obj;
    
    /**
     * Specified prefix-format (one char)
     * @var string
     */
    protected string $prefix_fmtChar = 'C';
    
    /**
     * Prefix-length
     * @var int
     */
    public int $prefix_fmtLen = 1;

    /**
     * Creating FieldsPack-router
     * 
     * @param array<string> $fields_un_arr
     * @param string|null $fmtChar
     * @param object|null $next_router_obj
     */
    public function __construct(array $fields_un_arr, ?string $fmtChar = 'C', ?object $next_router_obj = null) {
        $this->fields_un_arr = $fields_un_arr;
        $this->next_router_obj = $next_router_obj;
        if ($fmtChar && $fmtChar !== $this->prefix_fmtChar) {
            $this->setFmtChar($fmtChar);
        }
    }
    
    public function setFmtChar(string $fmtChar): int {
        $fmt_len = ('m' === $fmtChar) ? 3 : FieldsPackMain::fmtMultiBytesLen($fmtChar);
        if ($fmt_len) {
            $this->prefix_fmtLen = $fmt_len;
            $this->prefix_fmtChar = $fmtChar;
        }
        return $fmt_len;
    }
    
    public function setNextRouter(object $next_router_obj): void {
        $this->next_router_obj = $next_router_obj;
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
    
    public function unpackByPrefix(string $data): ?Result\Any {
        if (strlen($data) >= $this->prefix_fmtLen) {
            if (1 === $this->prefix_fmtLen) {
                $num = ord($data[0]);
            } elseif (3 === $this->prefix_fmtLen) {
                $num = (ord($data[0]) & 127) * 65536 + ord($data[1]) * 256 + ord($data[2]);
            } else {
                $num = unpack($this->prefix_fmtChar, substr($data, 0, $this->prefix_fmtLen))[1];
            }
            if (isset($this->fields_un_arr[$num])) {
                return $this->getFieldsPackByNum($num)->unpack(substr($data, $this->prefix_fmtLen));
            }
            if ($this->next_router_obj) {
                return $this->next_router_obj->unpackByPrefix($data);
            }
        }
        return NULL;
    }
    
    /**
     * Pack array by specified format-number and return result data with prefix
     * 
     * @param int $num
     * @param array<mixed> $arr
     * @return Result\Any|null
     */
    public function packByPrefix(int $num, array $arr): ?Result\Any {
        if (isset($this->fields_un_arr[$num])) {
            if ($this->prefix_fmtLen === 1) {
                if ($num > 127 || $num < 0) {
                    return new Result\Err("Num out of 7-bit-range");
                }
                $prefix = chr($num);
            } elseif ($this->prefix_fmtLen === 3) {
                $max = 8388608; // 2^23
                if ($num >= $max || $num < 128) {
                    return new Result\Err("Num out of 23-bit-range");
                }
                $prefix = substr(pack('N', $num | $max), -3);
            } else {
                $prefix = pack($this->prefix_fmtChar, $num);
            }            
            // num is local
            $result = $this->getFieldsPackByNum($num)->pack($arr);
            if (!$result->isErr()) {
                $result->setData($prefix . $result->getStr());
            }
            return $result;
        }

        // num is not local, try next router
        if ($this->next_router_obj) {
            return $this->next_router_obj->packByPrefix($num, $arr);
        }
        return NULL;
    }
}
