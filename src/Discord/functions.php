<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use ArrayIterator;
use Discord\Helpers\Deferred;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;
use Symfony\Component\OptionsResolver\Options;

/**
 * The HTML Color Table.
 *
 * @array HTML Color Table.
 */
const COLORTABLE = [
    'indianred' => 0xcd5c5c, 'lightcoral' => 0xf08080, 'salmon' => 0xfa8072, 'darksalmon' => 0xe9967a,
    'lightsalmon' => 0xffa07a, 'crimson' => 0xdc143c, 'red' => 0xff0000, 'firebrick' => 0xb22222,
    'darkred' => 0x8b0000, 'pink' => 0xffc0cb, 'lightpink' => 0xffb6c1, 'hotpink' => 0xff69b4,
    'deeppink' => 0xff1493, 'mediumvioletred' => 0xc71585, 'palevioletred' => 0xdb7093,
    'lightsalmon' => 0xffa07a, 'coral' => 0xff7f50, 'tomato' => 0xff6347, 'orangered' => 0xff4500,
    'darkorange' => 0xff8c00, 'orange' => 0xffa500, 'gold' => 0xffd700, 'yellow' => 0xffff00,
    'lightyellow' => 0xffffe0, 'lemonchiffon' => 0xfffacd, 'lightgoldenrodyellow' => 0xfafad2,
    'papayawhip' => 0xffefd5, 'moccasin' => 0xffe4b5, 'peachpuff' => 0xffdab9, 'palegoldenrod' => 0xeee8aa,
    'khaki' => 0xf0e68c, 'darkkhaki' => 0xbdb76b, 'lavender' => 0xe6e6fa, 'thistle' => 0xd8bfd8,
    'plum' => 0xdda0dd, 'violet' => 0xee82ee, 'orchid' => 0xda70d6, 'fuchsia' => 0xff00ff,
    'magenta' => 0xff00ff, 'mediumorchid' => 0xba55d3, 'mediumpurple' => 0x9370db, 'rebeccapurple' => 0x663399,
    'blueviolet' => 0x8a2be2, 'darkviolet' => 0x9400d3, 'darkorchid' => 0x9932cc, 'darkmagenta' => 0x8b008b,
    'purple' => 0x800080, 'indigo' => 0x4b0082, 'slateblue' => 0x6a5acd, 'darkslateblue' => 0x483d8b,
    'mediumslateblue' => 0x7b68ee, 'greenyellow' => 0xadff2f, 'chartreuse' => 0x7fff00, 'lawngreen' => 0x7cfc00,
    'lime' => 0x00ff00, 'limegreen' => 0x32cd32, 'palegreen' => 0x98fb98, 'lightgreen' => 0x90ee90,
    'mediumspringgreen' => 0x00fa9a, 'springgreen' => 0x00ff7f, 'mediumseagreen' => 0x3cb371,
    'seagreen' => 0x2e8b57, 'forestgreen' => 0x228b22, 'green' => 0x008000, 'darkgreen' => 0x006400,
    'yellowgreen' => 0x9acd32, 'olivedrab' => 0x6b8e23, 'olive' => 0x808000, 'darkolivegreen' => 0x556b2f,
    'mediumaquamarine' => 0x66cdaa, 'darkseagreen' => 0x8fbc8b, 'lightseagreen' => 0x20b2aa,
    'darkcyan' => 0x008b8b, 'teal' => 0x008080, 'aqua' => 0x00ffff, 'cyan' => 0x00ffff, 'lightcyan' => 0xe0ffff,
    'paleturquoise' => 0xafeeee, 'aquamarine' => 0x7fffd4, 'turquoise' => 0x40e0d0, 'mediumturquoise' => 0x48d1cc,
    'darkturquoise' => 0x00ced1, 'cadetblue' => 0x5f9ea0, 'steelblue' => 0x4682b4, 'lightsteelblue' => 0xb0c4de,
    'powderblue' => 0xb0e0e6, 'lightblue' => 0xadd8e6, 'skyblue' => 0x87ceeb, 'lightskyblue' => 0x87cefa,
    'deepskyblue' => 0x00bfff, 'dodgerblue' => 0x1e90ff, 'cornflowerblue' => 0x6495ed,
    'mediumslateblue' => 0x7b68ee, 'royalblue' => 0x4169e1, 'blue' => 0x0000ff, 'mediumblue' => 0x0000cd,
    'darkblue' => 0x00008b, 'navy' => 0x000080, 'midnightblue' => 0x191970, 'cornsilk' => 0xfff8dc,
    'blanchedalmond' => 0xffebcd, 'bisque' => 0xffe4c4, 'navajowhite' => 0xffdead, 'wheat' => 0xf5deb3,
    'burlywood' => 0xdeb887, 'tan' => 0xd2b48c, 'rosybrown' => 0xbc8f8f, 'sandybrown' => 0xf4a460,
    'goldenrod' => 0xdaa520, 'darkgoldenrod' => 0xb8860b, 'peru' => 0xcd853f, 'chocolate' => 0xd2691e,
    'saddlebrown' => 0x8b4513, 'sienna' => 0xa0522d, 'brown' => 0xa52a2a, 'maroon' => 0x800000,
    'white' => 0xffffff, 'snow' => 0xfffafa, 'honeydew' => 0xf0fff0, 'mintcream' => 0xf5fffa, 'azure' => 0xf0ffff,
    'aliceblue' => 0xf0f8ff, 'ghostwhite' => 0xf8f8ff, 'whitesmoke' => 0xf5f5f5, 'seashell' => 0xfff5ee,
    'beige' => 0xf5f5dc, 'oldlace' => 0xfdf5e6, 'floralwhite' => 0xfffaf0, 'ivory' => 0xfffff0,
    'antiquewhite' => 0xfaebd7, 'linen' => 0xfaf0e6, 'lavenderblush' => 0xfff0f5, 'mistyrose' => 0xffe4e1,
    'gainsboro' => 0xdcdcdc, 'lightgray' => 0xd3d3d3, 'silver' => 0xc0c0c0, 'darkgray' => 0xa9a9a9,
    'gray' => 0x808080, 'dimgray' => 0x696969, 'lightslategray' => 0x778899, 'slategray' => 0x708090,
    'darkslategray' => 0x2f4f4f, 'black' => 0x000000,
];

/**
 * Checks to see if a part has been mentioned.
 *
 * @param Part|string $part    The part or mention to look for.
 * @param Message     $message The message to check.
 *
 * @return bool Whether the part was mentioned.
 */
function mentioned($part, Message $message): bool
{
    if ($part instanceof User || $part instanceof Member) {
        return $message->mentions->has($part->id);
    } elseif ($part instanceof Role) {
        return $message->mention_roles->has($part->id);
    } elseif ($part instanceof Channel) {
        return strpos($message->content, "<#{$part->id}>") !== false;
    }

    return strpos($message->content, $part) !== false;
}

/**
 * Get int value for color.
 *
 * @param int|string $color The color's int, hexcode or htmlname.
 *
 * @return int color
 */
function getColor($color = 0): int
{
    if (is_integer($color)) {
        return $color;
    }

    if (preg_match('/^([a-z]+)$/ui', $color, $match)) {
        $colorName = strtolower($match[1]);
        if (isset(COLORTABLE[$colorName])) {
            return COLORTABLE[$colorName];
        }
    }

    if (preg_match('/^(#|0x|)([0-9a-f]{6})$/ui', $color, $match)) {
        return hexdec($match[2]);
    }

    return 0;
}

/**
 * Checks if a string contains an array of phrases.
 *
 * @param string $string  The string to check.
 * @param array  $matches Array containing one or more phrases to match.
 *
 * @return bool
 */
function contains(string $string, array $matches): bool
{
    foreach ($matches as $match) {
        if (strpos($string, $match) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Converts a string to studlyCase.
 *
 * @param string $string The string to convert.
 *
 * @return string
 */
function studly(string $string): string
{
    $ret = '';
    preg_match_all('/([a-z0-9]+)/ui', $string, $matches);

    foreach ($matches[0] as $match) {
        $ret .= ucfirst(strtolower($match));
    }

    return $ret;
}

/**
 * Polyfill to check if mbstring is installed.
 *
 * @param string $str
 *
 * @return int
 */
function poly_strlen($str)
{
    // If mbstring is installed, use it.
    if (function_exists('mb_strlen')) {
        return mb_strlen($str);
    }

    return strlen($str);
}

/**
 * Converts a file to base64 representation.
 *
 * @param string $filepath
 *
 * @return string
 */
function imageToBase64(string $filepath): string
{
    if (! file_exists($filepath)) {
        throw new \InvalidArgumentException('The given filepath does not exist.');
    }

    $mimetype = \mime_content_type($filepath);
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];

    if (! in_array($mimetype, $allowed)) {
        throw new \InvalidArgumentException('The given filepath is not one of jpeg, png or gif.');
    }

    $contents = file_get_contents($filepath);

    return "data:{$mimetype};base64,".base64_encode($contents);
}

/**
 * Takes a snowflake and calculates the time that the snowflake
 * was generated.
 *
 * @param string|float $snowflake
 *
 * @return float
 */
function getSnowflakeTimestamp(string $snowflake)
{
    if (\PHP_INT_SIZE === 4) { //x86
        $binary = \str_pad(\base_convert($snowflake, 10, 2), 64, 0, \STR_PAD_LEFT);
        $time = \base_convert(\substr($binary, 0, 42), 2, 10);
        $timestamp = (float) ((((int) \substr($time, 0, -3)) + 1420070400).'.'.\substr($time, -3));
        $workerID = (int) \base_convert(\substr($binary, 42, 5), 2, 10);
        $processID = (int) \base_convert(\substr($binary, 47, 5), 2, 10);
        $increment = (int) \base_convert(\substr($binary, 52, 12), 2, 10);
    } else { //x64
        $snowflake = (int) $snowflake;
        $time = (string) ($snowflake >> 22);
        $timestamp = (float) ((((int) \substr($time, 0, -3)) + 1420070400).'.'.\substr($time, -3));
        $workerID = ($snowflake & 0x3E0000) >> 17;
        $processID = ($snowflake & 0x1F000) >> 12;
        $increment = ($snowflake & 0xFFF);
    }
    if ($timestamp < 1420070400 || $workerID < 0 || $workerID >= 32 || $processID < 0 || $processID >= 32 || $increment < 0 || $increment >= 4096) {
        return null;
    }

    return $timestamp;
}

/**
 * For use with the Symfony options resolver.
 * For an option that takes a snowflake or part,
 * returns the snowflake or the value of `id_field`
 * on the part.
 *
 * @param string $id_field
 *
 * @internal
 */
function normalizePartId($id_field = 'id')
{
    return static function (Options $options, $part) use ($id_field) {
        if ($part instanceof Part) {
            return $part->{$id_field};
        }

        return $part;
    };
}

/**
 * Escape various Discord formatting and markdown into a plain text:
 * _Italics_, **Bold**, __Underline__, ~~Strikethrough~~, ||spoiler||
 * `Code`, ```Code block```, > Quotes, >>> Block quotes
 * #Channel @User
 * A backslash will be added before the each formatting symbol.
 *
 * @return string the escaped string unformatted as plain text
 */
function escapeMarkdown(string $text): string
{
    return addcslashes($text, '#*:>@_`|~');
}

/**
 * Run a deferred search in array.
 *
 * @param array|object  $array     Traversable, use $collection->getIterator() if searching in Collection
 * @param callable      $callback  The filter function to run
 * @param LoopInterface $loop      Loop interface, use $discord->getLoop()
 * @param callable      $canceller Deprecated, use `cancel()` from the returned promise
 *
 * @return Promise
 */
function deferFind($array, callable $callback, $loop, ?callable $canceller = null): ExtendedPromiseInterface
{
    $cancelled = false;
    $canceller ??= function () use (&$cancelled) {
        $cancelled = true;
    };
    $deferred = new Deferred($canceller);
    $iterator = new ArrayIterator($array);

    $loop->addPeriodicTimer(0.001, function ($timer) use ($loop, $deferred, $iterator, $callback, &$cancelled) {
        if ($cancelled) {
            $loop->cancelTimer($timer);

            return;
        }

        if (! $iterator->valid()) {
            $loop->cancelTimer($timer);
            $deferred->reject();

            return;
        }

        $current = $iterator->current();
        if ($callback($current)) {
            $loop->cancelTimer($timer);
            $deferred->resolve($current);

            return;
        }

        $iterator->next();
    });

    return $deferred->promise();
}
