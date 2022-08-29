<?php
namespace w3ocom\FieldsPack;

class FieldsPackMain implements FieldsPackInterface
{
    /**
     * How many bytes are in fixed-size part of fields-package
     * @var int
     */
    public int $fixed_len = 0;

    public string $fields_pk;
    public string $fields_un;

    /**
     * Fields array: field_name => field_fmtChar
     * @var array<string>
     */
    public array $fields_arr = [];

    /**
     * Field names with *-specified-length
     * @var array<string>
     */
    public array $_ext_arr = [];
    
    /**
     * if set to true, optional fields will be included to results of unpackArr
     * @var bool
     */
    public bool $inc_h = false;

    public function __construct(?string $fields_un = null)
    {
        if ($fields_un) {
            $err = $this->setFields($fields_un);
            if ($err->isErr()) {
                throw new \Exception($err->getErr());
            }
        }
    }

    public function setFields(string $fields_un, ?string $last_field = null): Result\Any
    {
        // parse fields to array
        $result = self::unpackFmtParse($fields_un);
        if ($result->isErr()) {
            return new Result\Err("Bad fields-format: '$fields_un' (" . $result->getErr() . ")");
        }

        $this->fields_arr = $fmt_arr = $result->getArr();

        // calculate header_bytes
        $fcnt = count($fmt_arr);
        $fmt = reset($fmt_arr);
        
        // Only for FieldsPackOpt ext
        if (isset($this->header_name) && (key($fmt_arr) === $this->header_name)) {
            $this->header_bytes = self::fmtMultiBytesLen($fmt);
            if (!$this->header_bytes) {
                return new Result\Err("Bad '{$this->header_name}' field format: $fmt");
            }
            if (--$fcnt > (8 * $this->header_bytes)) {
                return new Result\Err("Too many fields");
            }
        }

        // caclulate fixed_len, fields_un, fields_pk, _ext_arr
        $pk = [];
        $_ext_arr = [];
        $_ext_un = [];
        $fixed_len = 0;
        foreach ($fmt_arr as $name => $fmt) {
            if ('*' === substr($fmt, -1)) {
                $_ext_arr[] = $name;
                $fmt = substr($fmt, 0, -1);
                $len = self::fmtMultiBytesLen($fmt);
            } else {
                $st = pack($fmt, 0);
                $len = strlen($st);
            }
            if (!$len) {
                return new Result\Err("Bad format: $fmt");
            }
            $fixed_len += $len;
            $pk[] = $fmt;
            $_ext_un[] = $fmt . $name;
        }
        $this->fields_pk = implode('', $pk);
        $this->fixed_len = $fixed_len;
        if ($last_field !== '') {
            $_ext_un[] = $last_field ?? 'a**';
        }
        $this->fields_un = implode('/', $_ext_un);
        $this->_ext_arr = $_ext_arr;

        return new Result\OK;
    }

    /**
     * Convert header-fmt-char to pack-bytes-length
     *
     * @staticvar array $bl
     * @param string $fmtChar
     * @return integer
     */
    public static function fmtMultiBytesLen(string $fmtChar): int
    {
        static $fmtCharToLen = [
            'J' => 8,
            'Q' => 8,
            'P' => 8,
            'N' => 4,
            'L' => 4,
            'V' => 4,
            'l' => 4,
            'n' => 2,
            'v' => 2,
            'C' => 1,
        ];
        return isset($fmtCharToLen[$fmtChar]) ? $fmtCharToLen[$fmtChar] : 0;
    }

    /**
     * Convert unpack-format string to fields-array
     *
     * Return:
     *  Result\Arr = success. contain field items [name]=>pack-format
     *  Result\Err = error
     *
     * @param string $fields_un
     * @return Result\Any
     */
    public static function unpackFmtParse(string $fields_un): Result\Any
    {
        $fields_arr = [];
        $arr = empty($fields_un) ? [] : explode('/', $fields_un);
        foreach($arr as $f_n) {
            $p = 0;
            while (++$p <= strlen($f_n)) {
                $ch = substr($f_n, $p, 1);
                if (!is_numeric($ch) && ($ch !== '*')) {
                    $name = substr($f_n, $p);
                    if (!strlen($name)) {
                        return new Result\Err("Empty field name");
                    }
                    $fields_arr[$name] = substr($f_n, 0, $p);
                    break;
                }
            }
        }
        if (count($fields_arr) === count($arr)) {
            // Successful result is Array
            return new Result\Arr($fields_arr);
        }
        return new Result\Err("Error unpack-format parsing");
    }

    /**
     * Back-function for unpackFmtParse
     *
     * In: Array field_name => fmtChar
     * Out: Result\Str with packed-string
     *
     * @param array<string> $fields_arr
     * @return Result\Any
     */
    public static function packFmtFields(array $fields_arr): Result\Any
    {
        $arr = [];
        foreach($fields_arr as $name => $fmtChar) {
            $arr[] = $fmtChar . $name;
        }
        return new Result\Str(implode('/', $arr));
    }

    /**
     * Pack array to string
     * 
     * In: array of field_name => field_value
     * Out: Result\Str = success
     * 
     * @param array<mixed> $arr
     * @return Result\Any
     */
    public function pack(array $arr): Result\Any
    {
        $wr_arr = [];
        $ex_str = '';
        foreach($this->fields_arr as $name => $fmt) {
            if (substr($fmt, 1, 1) === '*') {
                $ex_str .= $v = isset($arr[$name]) ? $arr[$name] : '';
                $wr_arr[] = pack(substr($fmt, 0, 1), strlen($v));
            } else {
                $v = isset($arr[$name]) ? $arr[$name] : 0;
                $wr_arr[] = pack($fmt, $v);
            }

        }
        $ext = isset($arr['*']) ? $arr['*'] : '';
        return new Result\Str(implode('', $wr_arr) . $ex_str . $ext);
    }

    public function unpack(string $_raw): Result\Any
    {
        $l = strlen($_raw);
        if ($l < $this->fixed_len) {
            return new Result\Err("String too short");
        }
        //$fix_str = substr($_raw, 0, $this->fixed_len);
        $arr = unpack($this->fields_un, $_raw);
        foreach($this->_ext_arr as $name) {
            $len = $arr[$name];
            $arr[$name] = substr($arr['*'], 0, $len);
            $arr['*'] = substr($arr['*'], $len);
        }
        if ($this->inc_h) {
            $_fmt = $this->fields_un;
            $arr['_h'] = compact('_fmt', '_raw');
        }
        return new Result\Arr($arr);
    }
}