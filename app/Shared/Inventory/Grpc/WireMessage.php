<?php

namespace App\Shared\Inventory\Grpc;

use InvalidArgumentException;

abstract class WireMessage
{
    protected static function uint(int $field, int $value): string
    {
        return self::varint(($field << 3) | 0).self::varint($value);
    }

    protected static function string(int $field, string $value): string
    {
        return self::bytes($field, $value);
    }

    protected static function message(int $field, self $message): string
    {
        return self::bytes($field, $message->serializeToString());
    }

    private static function bytes(int $field, string $value): string
    {
        return self::varint(($field << 3) | 2).self::varint(strlen($value)).$value;
    }

    private static function varint(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Only unsigned protobuf values are supported.');
        }

        $encoded = '';

        while ($value > 127) {
            $encoded .= chr(($value & 127) | 128);
            $value = intdiv($value, 128);
        }

        return $encoded.chr($value);
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    protected static function fields(string $payload): array
    {
        $offset = 0;
        $length = strlen($payload);
        $fields = [];

        while ($offset < $length) {
            $key = self::readVarint($payload, $offset);
            $number = $key >> 3;
            $wireType = $key & 7;

            if ($number === 0) {
                throw new InvalidArgumentException('Invalid protobuf field number.');
            }

            if ($wireType === 0) {
                $fields[$number][] = self::readVarint($payload, $offset);
                continue;
            }

            if ($wireType === 2) {
                $size = self::readVarint($payload, $offset);

                if ($size > $length - $offset) {
                    throw new InvalidArgumentException('Invalid protobuf length-delimited field.');
                }

                $fields[$number][] = substr($payload, $offset, $size);
                $offset += $size;
                continue;
            }

            throw new InvalidArgumentException('Unsupported protobuf wire type.');
        }

        return $fields;
    }

    private static function readVarint(string $payload, int &$offset): int
    {
        $value = 0;
        $shift = 0;
        $length = strlen($payload);

        while ($offset < $length && $shift <= 63) {
            $byte = ord($payload[$offset++]);
            $value |= ($byte & 127) << $shift;

            if (($byte & 128) === 0) {
                return $value;
            }

            $shift += 7;
        }

        throw new InvalidArgumentException('Invalid protobuf varint.');
    }

    abstract public function serializeToString(): string;
}
