<?php


namespace Discord\Helpers;

/**
 * @link https://www.php.net/manual/en/function.pack.php
 */
enum FormatPackEnum: string
{
    /**
     * NUL-padded string
     */
    case a = 'a';

    /**
     * SPACE-padded string
     */
    case A = 'A';

    /**
     * Hex string, low nibble first
     */
    case h = 'h';

    /**
     * Hex string, high nibble first
     */
    case H = 'H';

    /**
     * signed char
     */
    case c = 'c';

    /**
     * unsigned char
     */
    case C = 'C';

    /**
     * signed short (always 16 bit, machine byte order)
     */
    case s = 's';

    /**
     * unsigned short (always 16 bit, machine byte order)
     */
    case S = 'S';

    /**
     * unsigned short (always 16 bit, big endian byte order)
     */
    case n = 'n';

    /**
     * unsigned short (always 16 bit, little endian byte order)
     */
    case v = 'v';

    /**
     * signed integer (machine dependent size and byte order)
     */
    case i = 'i';

    /**
     * unsigned integer (machine dependent size and byte order)
     */
    case I = 'I';

    /**
     * signed long (always 32 bit, machine byte order)
     */
    case l = 'l';

    /**
     * unsigned long (always 32 bit, machine byte order)
     */
    case L = 'L';

    /**
     * unsigned long (always 32 bit, big endian byte order)
     */
    case N = 'N';

    /**
     * unsigned long (always 32 bit, little endian byte order)
     */
    case V = 'V';

    /**
     * signed long long (always 64 bit, machine byte order)
     */
    case q = 'q';

    /**
     * unsigned long long (always 64 bit, machine byte order)
     */
    case Q = 'Q';

    /**
     * unsigned long long (always 64 bit, big endian byte order)
     */
    case J = 'J';

    /**
     * unsigned long long (always 64 bit, little endian byte order)
     */
    case P = 'P';

    /**
     * float (machine dependent size and representation)
     */
    case f = 'f';

    /**
     * float (machine dependent size, little endian byte order)
     */
    case g = 'g';

    /**
     * float (machine dependent size, big endian byte order)
     */
    case G = 'G';

    /**
     * double (machine dependent size and representation)
     */
    case d = 'd';

    /**
     * double (machine dependent size, little endian byte order)
     */
    case e = 'e';

    /**
     * double (machine dependent size, big endian byte order)
     */
    case E = 'E';

    /**
     * NUL byte
     */
    case x = 'x';

    /**
     * Back up one byte
     */
    case X = 'X';

    /**
     * NUL-padded string
     */
    case Z = 'Z';

    /**
     * NUL-fill to absolute position
     */
    case At = '@';

    public function getLength(): int
    {
        return match ($this) {
            self::n, self::v => 2,
            self::N, self::V => 4,
            self::c, self::C => 1,
            default => throw new \InvalidArgumentException('Invalid format pack'),
        };
    }
}
