<?php

namespace CyberDeep\LaravelAgoraTokenGenerator\Services\Token;

class Util
{
    public static function packUint16($value)
    {
        return pack("v", $value);
    }

    public static function unpackUint16(&$data)
    {
        $result = unpack("v", substr($data, 0, 2));
        $data = substr($data, 2);
        return $result[1];
    }

    public static function packUint32($value)
    {
        return pack("V", $value);
    }

    public static function unpackUint32(&$data)
    {
        $result = unpack("V", substr($data, 0, 4));
        $data = substr($data, 4);
        return $result[1];
    }

    public static function packInt16($value)
    {
        return pack("s", $value);
    }

    public static function unpackInt16(&$data)
    {
        $result = unpack("s", substr($data, 0, 2));
        $data = substr($data, 2);
        return $result[1];
    }

    public static function packString($value)
    {
        return self::packUint16(strlen($value)) . $value;
    }

    public static function unpackString(&$data)
    {
        $len = self::unpackUint16($data);
        $result = substr($data, 0, $len);
        $data = substr($data, $len);
        return $result;
    }

    public static function packMapUint32($map)
    {
        $data = self::packUint16(count($map));
        foreach ($map as $key => $value) {
            $data .= self::packUint16($key);
            $data .= self::packUint32($value);
        }
        return $data;
    }

    public static function unpackMapUint32(&$data)
    {
        $map = array();
        $count = self::unpackUint16($data);
        for ($i = 0; $i < $count; $i++) {
            $key = self::unpackUint16($data);
            $value = self::unpackUint32($data);
            $map[$key] = $value;
        }
        return $map;
    }
}