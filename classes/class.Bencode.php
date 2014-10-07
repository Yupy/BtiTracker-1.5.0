<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/
/**
 * Main class that exposes methods to serialize/unserialize objects to/from
 * Bencoded string buffers.
 *
 * @link http://en.wikipedia.org/wiki/Bencode
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Bencode
{
    const VERSION = '1.0.1';

    /**
     * Serializes an object to its Bencode representation.
     *
     * @param int|string|array|boolean $object Object to serialize.
     * @return string
     */
    public static function encode($object)
    {
        $serialized = "";
        self::decideEncode($object, $serialized);

        return $serialized;
    }

    /**
     * Handles the serialization of one of the supported object types.
     *
     * @param int|string|array|boolean $object Object to serialize.
     * @param string $serialized Reference to the serialization buffer.
     */
    private static function decideEncode($object, &$serialized)
    {
        switch ($type = gettype($object)) {
            case 'integer':
                $serialized .= "i{$object}e";
                break;

            case 'string':
                self::encodeString($object, $serialized);
                break;

            case 'array':
                $function = self::getArraySerializer($object);
                self::$function($object, $serialized);
                break;

            case 'boolean':
                $serialized .= $object === true ? 'i1e' : 'i0e';
                break;

            default:
                throw new EncodingException("Invalid type for encoding: $type", $object);
        }
    }

    /**
     * Handles the serialization of a string object.
     *
     * @param string $string String to serialize.
     * @param string $serialized Reference to the serialization buffer.
     */
    private static function encodeString($string, &$serialized)
    {
        $serialized .= strlen($string) . ":$string";
    }

    /**
     * Handles the serialization of an array object.
     *
     * @param string $list Array to serialize.
     * @param string $serialized Reference to the serialization buffer.
     */
    private static function encodeList($list, &$serialized)
    {
        if (empty($list)) {
            $serialized .= "le";
            return;
        }

        $serialized .= "l";
        for ($i = 0; isset($list[$i]); $i++) {
            self::decideEncode($list[$i], $serialized);
        }
        $serialized .= "e";
    }

    /**
     * Handles the serialization of a named array object.
     *
     * @param string $list Named array to serialize.
     * @param string $serialized Reference to the serialization buffer.
     */
    private static function encodeDictionary($dictionary, &$serialized)
    {
        if (empty($dictionary)) {
            $serialized .= "de";
            return;
        }

        $serialized .= "d";
        foreach(self::sortDictionary($dictionary) as $key => $value) {
            self::encodeString($key, $serialized);
            self::decideEncode($value, $serialized);
        }
        $serialized .= "e";
    }

    /**
     * Inspects the passed array and returns a serialization strategy
     * depending on the type of its keys. If the array contains at least
     * one string key, it is treated as a named array.
     *
     * @param array $array Array to serialize serialize.
     * @return string
     */
    private static function getArraySerializer($array)
    {
        if (empty($array)) {
            return 'encodeList';
        }

        foreach (array_keys($array) as $key) {
            if (is_string($key)) {
                return 'encodeDictionary';
            }
        }

        return 'encodeList';
    }

    /**
     * Sorts a named array by its keys.
     *
     * @param array $dictionary Named array to sort.
     * @param array
     */
    private static function sortDictionary($dictionary)
    {
        if (!ksort($dictionary, SORT_STRING)) {
            throw new EncodingException('Failed to sort dictionary', $dictionary);
        }

        return $dictionary;
    }

    /**
     * Handles the deserialization of a string containing the Bencode representation
     * of an object.
     *
     * @param string $buffer Buffer containing the serialized data.
     * @return mixed
     */
    public static function decode($buffer)
    {
        $offset = 0;
        $object = self::decodeEntry($buffer, $offset);

        return $object;
    }

    /**
     * Handles the deserialization of the Bencode representation of an object from a file.
     *
     * @param string $path Path of the file.
     * @return mixed
     */
    public static function decodeFromFile($path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File $path does not exist");
        }

        $buffer = file_get_contents($path);
        $object = self::decode($buffer);

        return $object;
    }

    /**
     * Decodes the next Bencode entry from the buffer.
     *
     * @param string $buffer Buffer containing the Bencode string.
     * @param int $offset Current position in the buffer.
     * @return mixed
     */
    private static function decodeEntry($buffer, &$offset)
    {
        $offsetMarker = $offset;

        switch ($byte = $buffer[$offset++]) {
            case 'd':
                $dictionary = array();
                for (;;) {
                    if ($buffer[$offset] === 'e') {
                        $offset++;
                        break;
                    }

                    $key = self::decodeEntry($buffer, $offset);
                    if (!is_string($key) && !is_numeric($key)) {
                        throw new DecodingException("One of the dictionary keys is not a string or an integer: " . gettype($key), $offset);
                    }
                    $dictionary[$key] = self::decodeEntry($buffer, $offset);
                }
                return $dictionary;

            case 'l':
                $list = array();
                for (;;) {
                    if ($buffer[$offset] === 'e') {
                        $offset++;
                        break;
                    }
                    $list[] = self::decodeEntry($buffer, $offset);
                }
                return $list;

            case 'e':
            case 'i':
                return self::getIntegerFromBuffer($buffer, $offset);

            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                $number = self::getStringFromBuffer($buffer, $offsetMarker);
                $offset = $offsetMarker;
                return $number;

            default:
                throw new DecodingException("Unknown prefix: $byte", $offset);
        }
    }

    /**
     * Decodes a string from the buffer.
     *
     * @param string $buffer Buffer containing the Bencode string.
     * @param int $offset Current position in the buffer.
     * @return string
     */
    private static function getStringFromBuffer($buffer, &$offset)
    {
        if (($length = self::getIntegerFromBuffer($buffer, $offset, ':')) < 0) {
            throw new DecodingException("Invalid string length: $length", $offset);
        }

        if ($length == 0) {
            return '';
        }

        $string = substr($buffer, $offset, $length);
        $offset += $length;

        return $string;
    }

    /**
     * Decodes an integer from the buffer.
     *
     * @param string $buffer Buffer containing the Bencode string.
     * @param int $offset Current position in the buffer.
     * @param string $delimiter Delimiter for the serialized representation of the integer.
     * @return int
     */
    private static function getIntegerFromBuffer($buffer, &$offset, $delimiter = 'e')
    {
        $numeric = '';
        $offsetMarker = $offset;

        while (($byte = $buffer[$offset++]) !== $delimiter) {
            $numeric .= $byte;
        }

        $integer = (int) $numeric;
        if ($numeric != $integer) {
            throw new DecodingException("Invalid integer: $numeric", $offsetMarker);
        }

        return $integer;
    }

    /**
     * Converts Bencode representation to JSON representation.
     *
     * @param string $bencode Buffer containing the Bencode string.
     * @return string
     */
    public static function convertToJSON($bencode, $options = 0)
    {
        $object = self::decode($bencode);
        $json = json_encode($object, $options);

        return $json;
    }

    /**
     * Converts JSON representation to Bencode representation.
     *
     * @param string $json Buffer containing the JSON string.
     * @return string
     */
    public static function convertFromJSON($json)
    {
        $object = json_decode($json, true);
        $bencode = self::encode($object);

        return $bencode;
    }
}

?>
