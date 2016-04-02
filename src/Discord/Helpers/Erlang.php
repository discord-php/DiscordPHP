<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

// ex: set ft=php fenc=utf-8 sts=4 ts=4 sw=4 et nomod:
//
// BSD LICENSE
//
// Copyright (c) 2014, Michael Truog <mjtruog at gmail dot com>
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are met:
//
//     * Redistributions of source code must retain the above copyright
//       notice, this list of conditions and the following disclaimer.
//     * Redistributions in binary form must reproduce the above copyright
//       notice, this list of conditions and the following disclaimer in
//       the documentation and/or other materials provided with the
//       distribution.
//     * All advertising materials mentioning features or use of this
//       software must display the following acknowledgment:
//         This product includes software developed by Michael Truog
//     * The name of the author may not be used to endorse or promote
//       products derived from this software without specific prior
//       written permission
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
// CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
// INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
// OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
// DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
// CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
// SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
// BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
// INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
// WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
// NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
// OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
// DAMAGE.
//

namespace Discord\Helpers;

// tag values here http://www.erlang.org/doc/apps/erts/erl_ext_dist.html
define(__NAMESPACE__.'\TAG_VERSION', 131);
define(__NAMESPACE__.'\TAG_COMPRESSED_ZLIB', 80);
define(__NAMESPACE__.'\TAG_NEW_FLOAT_EXT', 70);
define(__NAMESPACE__.'\TAG_BIT_BINARY_EXT', 77);
define(__NAMESPACE__.'\TAG_ATOM_CACHE_REF', 78);
define(__NAMESPACE__.'\TAG_SMALL_INTEGER_EXT', 97);
define(__NAMESPACE__.'\TAG_INTEGER_EXT', 98);
define(__NAMESPACE__.'\TAG_FLOAT_EXT', 99);
define(__NAMESPACE__.'\TAG_ATOM_EXT', 100);
define(__NAMESPACE__.'\TAG_REFERENCE_EXT', 101);
define(__NAMESPACE__.'\TAG_PORT_EXT', 102);
define(__NAMESPACE__.'\TAG_PID_EXT', 103);
define(__NAMESPACE__.'\TAG_SMALL_TUPLE_EXT', 104);
define(__NAMESPACE__.'\TAG_LARGE_TUPLE_EXT', 105);
define(__NAMESPACE__.'\TAG_NIL_EXT', 106);
define(__NAMESPACE__.'\TAG_STRING_EXT', 107);
define(__NAMESPACE__.'\TAG_LIST_EXT', 108);
define(__NAMESPACE__.'\TAG_BINARY_EXT', 109);
define(__NAMESPACE__.'\TAG_SMALL_BIG_EXT', 110);
define(__NAMESPACE__.'\TAG_LARGE_BIG_EXT', 111);
define(__NAMESPACE__.'\TAG_NEW_FUN_EXT', 112);
define(__NAMESPACE__.'\TAG_EXPORT_EXT', 113);
define(__NAMESPACE__.'\TAG_NEW_REFERENCE_EXT', 114);
define(__NAMESPACE__.'\TAG_SMALL_ATOM_EXT', 115);
define(__NAMESPACE__.'\TAG_MAP_EXT', 116);
define(__NAMESPACE__.'\TAG_FUN_EXT', 117);
define(__NAMESPACE__.'\TAG_ATOM_UTF8_EXT', 118);
define(__NAMESPACE__.'\TAG_SMALL_ATOM_UTF8_EXT', 119);

class OtpErlangAtom
{
    public $value;
    public $utf8;
    public function __construct($value, $utf8 = false)
    {
        $this->value = $value;
        $this->utf8  = $utf8;
    }
    public function binary()
    {
        if (is_int($this->value)) {
            return pack('CC', TAG_ATOM_CACHE_REF, $this->value);
        } elseif (is_string($this->value)) {
            $size = strlen($this->value);
            if ($this->utf8) {
                if ($size < 256) {
                    return pack('CC', TAG_SMALL_ATOM_UTF8_EXT, $size).
                           $this->value;
                } else {
                    return pack('Cn', TAG_ATOM_UTF8_EXT, $size).
                           $this->value;
                }
            } else {
                if ($size < 256) {
                    return pack('CC', TAG_SMALL_ATOM_EXT, $size).
                           $this->value;
                } else {
                    return pack('Cn', TAG_ATOM_EXT, $size).
                           $this->value;
                }
            }
        } else {
            throw new OutputException('unknown atom type');
        }
    }
    public function __toString()
    {
        return sprintf('%s(%s,utf8=%s)', get_class(),
                       $this->value, $this->utf8 ? 'true' : 'false');
    }
}

class OtpErlangList
{
    public $value;
    public $improper;
    public function __construct($value, $improper = false)
    {
        $this->value    = $value;
        $this->improper = $improper;
    }
    public function binary()
    {
        if (is_array($this->value)) {
            $length = count($this->value);
            if ($length == 0) {
                return chr(TAG_NIL_EXT);
            } elseif ($this->improper) {
                $contents = '';
                while (list($tmp, $element) = each($this->value)) {
                    $contents .= _term_to_binary($element);
                }
                reset($this->value);

                return pack('CN', TAG_LIST_EXT, $length - 1).$contents;
            } else {
                $contents = '';
                while (list($tmp, $element) = each($this->value)) {
                    $contents .= _term_to_binary($element);
                }
                reset($this->value);

                return pack('CN', TAG_LIST_EXT, $length).$contents.
                       chr(TAG_NIL_EXT);
            }
        } else {
            throw new OutputException('unknown list type');
        }
    }
    public function __toString()
    {
        return sprintf('%s(array(%s),improper=%s)', get_class(),
                       implode(',', $this->value),
                       $this->improper ? 'true' : 'false');
    }
}

class OtpErlangBinary
{
    public $value;
    public $bits;
    public function __construct($value, $bits = 8)
    {
        $this->value = $value;
        $this->bits  = $bits;
    }
    public function binary()
    {
        if (is_string($this->value)) {
            $size = strlen($this->value);
            if ($this->bits != 8) {
                return pack('CNC', TAG_BIT_BINARY_EXT, $size,
                            $this->bits).$this->value;
            } else {
                return pack('CN', TAG_BINARY_EXT, $size).$this->value;
            }
        } else {
            dump($this->value);
            throw new OutputException('unknown binary type');
        }
    }
    public function __toString()
    {
        return sprintf('%s(%s,bits=%d)', get_class(),
                       $this->value, $this->bits);
    }
}

class OtpErlangFunction
{
    public $tag;
    public $value;
    public function __construct($tag, $value)
    {
        $this->tag   = $tag;
        $this->value = $value;
    }
    public function binary()
    {
        return chr($this->tag).$this->value;
    }
    public function __toString()
    {
        return sprintf('%s(%s,%s)', get_class(),
                       $this->tag, $this->value);
    }
}

class OtpErlangReference
{
    public $node;
    public $id;
    public $creation;
    public function __construct($node, $id, $creation)
    {
        $this->node     = $node;
        $this->id       = $id;
        $this->creation = $creation;
    }
    public function binary()
    {
        $size = intval(strlen($this->id) / 4);
        if ($size > 1) {
            return pack('Cn', TAG_NEW_REFERENCE_EXT, $size).
                   $this->node->binary().$this->creation.$this->id;
        } else {
            return chr(TAG_REFERENCE_EXT).
                   $this->node->binary().$this->id.$this->creation;
        }
    }
    public function __toString()
    {
        return sprintf('%s(%s,%s,%s)', get_class(),
                       $this->node, $this->id, $this->creation);
    }
}

class OtpErlangPort
{
    public $node;
    public $id;
    public $creation;
    public function __construct($node, $id, $creation)
    {
        $this->node     = $node;
        $this->id       = $id;
        $this->creation = $creation;
    }
    public function binary()
    {
        return chr(TAG_PORT_EXT).
               $this->node->binary().$this->id.$this->creation;
    }
    public function __toString()
    {
        return sprintf('%s(%s,%s,%s)', get_class(),
                       $this->node, $this->id, $this->creation);
    }
}

class OtpErlangPid
{
    public $node;
    public $id;
    public $serial;
    public $creation;
    public function __construct($node, $id, $serial, $creation)
    {
        $this->node     = $node;
        $this->id       = $id;
        $this->serial   = $serial;
        $this->creation = $creation;
    }
    public function binary()
    {
        return chr(TAG_PID_EXT).$this->node->binary().
               $this->id.$this->serial.$this->creation;
    }
    public function __toString()
    {
        return sprintf('%s(%s,%s,%s,%s)', get_class(),
                       $this->node, $this->id, $this->serial, $this->creation);
    }
}

class OtpErlangMap
{
    public $pairs;
    public function __construct($pairs)
    {
        $this->pairs = $pairs;
    }
    public function binary()
    {
        $arity       = count($this->pairs);
        $term_packed = '';
        foreach ($this->pairs as $pair) {
            list($key, $value) = $pair;
            $key_packed        = _term_to_binary($key);
            $value_packed      = _term_to_binary($value);
            $term_packed .= $key_packed.$value_packed;
        }

        return pack('CN', TAG_MAP_EXT, $arity).$term_packed;
    }
    public function __toString()
    {
        return sprintf('%s(%d)', get_class(),
                       count($this->pairs));
    }
}

function _error_handler($errno = 0, $errstr = null,
                        $errfile = null, $errline = null)
{
    // If error is suppressed with @, don't throw an exception
    if (error_reporting() === 0) {
        return true;
    } // return true to continue through the others handlers
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function binary_to_term($data)
{
    if (! is_string($data)) {
        throw new ParseException('not bytes input');
    }
    $size = strlen($data);
    if ($size <= 1) {
        throw new ParseException('null input');
    }
    if (ord($data[0]) != TAG_VERSION) {
        throw new ParseException('invalid version');
    }
    set_error_handler(__NAMESPACE__.'\_error_handler');
    try {
        list($i, $term) = _binary_to_term(1, $data);
        restore_error_handler();
        if ($i != $size) {
            throw new ParseException('unparsed data');
        }

        return $term;
    } catch (\ErrorException $e) {
        restore_error_handler();
        throw new ParseException((string) $e);
    }
}

function term_to_binary($term, $compressed = false)
{
    $data_uncompressed = _term_to_binary($term);
    if ($compressed === false) {
        return chr(TAG_VERSION).$data_uncompressed;
    } else {
        if ($compressed === true) {
            $compressed = 6;
        }
        if ($compressed < 0 || $compressed > 9) {
            throw new InputException('compressed in [0..9]');
        }
        $data_compressed   = gzcompress($data_uncompressed, $compressed);
        $size_uncompressed = strlen($data_uncompressed);

        return pack('CCN', TAG_VERSION, TAG_COMPRESSED_ZLIB,
                    $size_uncompressed).$data_compressed;
    }
}

function _binary_to_term($i, $data)
{
    $tag = ord($data[$i]);
    $i += 1;
    switch ($tag) {
        case TAG_NEW_FLOAT_EXT:
            if (unpack('S', "\x01\x00") == [1 => 1]) { // little endian
                list(, $value) = unpack('d', strrev(substr($data, $i, 8)));
            } else {
                list(, $value) = unpack('d', substr($data, $i, 8));
            }

            return [$i + 8, $value];
        case TAG_BIT_BINARY_EXT:
            list(, $j) = unpack('N', substr($data, $i, 4));
            $i += 4;
            $bits = ord($data[$i]);
            $i += 1;

            return [$i + $j,
                         new OtpErlangBinary(substr($data, $i, $j), $bits), ];
        case TAG_ATOM_CACHE_REF:
            return [$i + 1, new OtpErlangAtom(ord($data[$i]))];
        case TAG_SMALL_INTEGER_EXT:
            return [$i + 1, ord($data[$i])];
        case TAG_INTEGER_EXT:
            list(, $value) = unpack('N', substr($data, $i, 4));
            if ($value & 0x80000000) {
                $value = -2147483648 + ($value & 0x7fffffff);
            }

            return [$i + 4, $value];
        case TAG_FLOAT_EXT:
            return [$i + 31, floatval(substr($data, $i, 31))];
        case TAG_ATOM_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $i += 2;

            return [$i + $j, new OtpErlangAtom(substr($data, $i, $j))];
        case TAG_REFERENCE_EXT:
        case TAG_PORT_EXT:
            list($i, $node) = _binary_to_atom($i, $data);
            $id             = substr($data, $i, 4);
            $i += 4;
            $creation = $data[$i];
            $i += 1;
            if ($tag == TAG_REFERENCE_EXT) {
                return [$i, new OtpErlangReference($node, $id, $creation)];
            } elseif ($tag == TAG_PORT_EXT) {
                return [$i, new OtpErlangPort($node, $id, $creation)];
            }
        case TAG_PID_EXT:
            list($i, $node) = _binary_to_atom($i, $data);
            $id             = substr($data, $i, 4);
            $i += 4;
            $serial = substr($data, $i, 4);
            $i += 4;
            $creation = $data[$i];
            $i += 1;

            return [$i, new OtpErlangPid($node, $id, $serial, $creation)];
        case TAG_SMALL_TUPLE_EXT:
        case TAG_LARGE_TUPLE_EXT:
            if ($tag == TAG_SMALL_TUPLE_EXT) {
                $arity = ord($data[$i]);
                $i += 1;
            } elseif ($tag == TAG_LARGE_TUPLE_EXT) {
                list(, $arity) = unpack('N', substr($data, $i, 4));
                $i += 4;
            }

            return _binary_to_term_sequence($i, $arity, $data);
        case TAG_NIL_EXT:
            return [$i, new OtpErlangList([])];
        case TAG_STRING_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $i += 2;

            return [$i + $j, substr($data, $i, $j)];
        case TAG_LIST_EXT:
            list(, $arity) = unpack('N', substr($data, $i, 4));
            $i += 4;
            list($i, $tmp)  = _binary_to_term_sequence($i, $arity, $data);
            list($i, $tail) = _binary_to_term($i, $data);
            if (get_class($tail) != __NAMESPACE__.'\OtpErlangList' or
                $tail->value != []) {
                $tmp[] = $tail;
                $tmp   = new OtpErlangList($tmp, true);
            } else {
                $tmp = new OtpErlangList($tmp);
            }

            return [$i, $tmp];
        case TAG_BINARY_EXT:
            list(, $j) = unpack('N', substr($data, $i, 4));
            $i += 4;

            return [$i + $j,
                         new OtpErlangBinary(substr($data, $i, $j), 8), ];
        case TAG_SMALL_BIG_EXT:
        case TAG_LARGE_BIG_EXT:
            if ($tag == TAG_SMALL_BIG_EXT) {
                $j = ord($data[$i]);
                $i += 1;
            } elseif ($tag == TAG_LARGE_BIG_EXT) {
                list(, $j) = unpack('N', substr($data, $i, 4));
                $i += 4;
            }
            $sign   = ord($data[$i]);
            $bignum = 0;
            if ($j > 0) {
                foreach (range(0, $j - 1) as $bignum_index) {
                    $digit  = ord($data[$i + $j - $bignum_index]);
                    $bignum = $bignum * 256 + $digit;
                }
            }
            if ($sign == 1) {
                $bignum *= -1;
            }
            $i += 1;

            return [$i + $j, $bignum];
        case TAG_NEW_FUN_EXT:
            list(, $size) = unpack('N', substr($data, $i, 4));

            return [$i + $size,
                         new OtpErlangFunction($tag, substr($data, $i, $size)), ];
        case TAG_EXPORT_EXT:
            $old_i              = $i;
            list($i, $module)   = _binary_to_atom($i, $data);
            list($i, $function) = _binary_to_atom($i, $data);
            if (ord($data[$i]) != TAG_SMALL_INTEGER_EXT) {
                throw new ParseException('invalid small integer tag');
            }
            $i += 1;
            $arity = ord($data[$i]);
            $i += 1;

            return [$i,
                         new OtpErlangFunction($tag,
                                               substr($data,
                                                      $old_i, $i - $old_i)), ];
        case TAG_NEW_REFERENCE_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $j *= 4;
            $i += 2;
            list($i, $node) = _binary_to_atom($i, $data);
            $creation       = $data[$i];
            $i += 1;
            $id = substr($data, $i, $j);

            return [$i + $j,
                         new OtpErlangReference($node, $id, $creation), ];
        case TAG_SMALL_ATOM_EXT:
            $j = ord($data[$i]);
            $i += 1;
            $atom_name = substr($data, $i, $j);
            if ($atom_name == 'true') {
                $tmp = true;
            } elseif ($atom_name == 'false') {
                $tmp = false;
            } else {
                $tmp = new OtpErlangAtom($atom_name);
            }

            return [$i + $j, $tmp];
        case TAG_MAP_EXT:
            list(, $arity) = unpack('N', substr($data, $i, 4));
            $i += 4;
            $pairs = [];
            if ($arity > 0) {
                foreach (range(0, $arity - 1) as $arity_index) {
                    list($i, $key)   = _binary_to_term($i, $data);
                    list($i, $value) = _binary_to_term($i, $data);
                    $pairs[]         = [$key, $value];
                }
            }

            return [$i, new OtpErlangMap($pairs)];
        case TAG_FUN_EXT:
            $old_i           = $i;
            list(, $numfree) = unpack('N', substr($data, $i, 4));
            $i += 4;
            list($i, $pid)         = _binary_to_pid($i, $data);
            list($i, $name_module) = _binary_to_atom($i, $data);
            list($i, $index)       = _binary_to_integer($i, $data);
            list($i, $uniq)        = _binary_to_integer($i, $data);
            list($i, $free)        = _binary_to_term_sequence($i, $numfree, $data);

            return [$i,
                         new OtpErlangFunction($tag,
                                               substr($data,
                                                      $old_i, $i - $old_i)), ];
        case TAG_ATOM_UTF8_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $i += 2;
            $atom_name = substr($data, $i, $j);

            return [$i + $j, new OtpErlangAtom($atom_name, true)];
        case TAG_SMALL_ATOM_UTF8_EXT:
            $j = ord($data[$i]);
            $i += 1;
            $atom_name = substr($data, $i, $j);

            return [$i + $j, new OtpErlangAtom($atom_name, true)];
        case TAG_COMPRESSED_ZLIB:
            list(, $size_uncompressed) = unpack('N', substr($data, $i, 4));
            if ($size_uncompressed == 0) {
                throw new ParseException('compressed data null');
            }
            $i += 4;
            $data_compressed   = substr($data, $i);
            $j                 = strlen($data_compressed);
            $data_uncompressed = gzuncompress($data_compressed);
            if ($size_uncompressed != strlen($data_uncompressed)) {
                throw new ParseException('compression corrupt');
            }
            list($i_new, $term) = _binary_to_term(0, $data_uncompressed);
            if ($i_new != $size_uncompressed) {
                throw new ParseException('unparsed data');
            }

            return [$i + $j, $term];
        default:
            throw new ParseException('invalid tag');
    }
}

function _binary_to_term_sequence($i, $arity, $data)
{
    $sequence = [];
    if ($arity > 0) {
        foreach (range(0, $arity - 1) as $arity_index) {
            list($i, $element) = _binary_to_term($i, $data);
            $sequence[]        = $element;
        }
    }

    return [$i, $sequence];
}

function _binary_to_integer($i, $data)
{
    $tag = ord($data[$i]);
    $i += 1;
    if ($tag == TAG_SMALL_INTEGER_EXT) {
        return [$i + 1, ord($data[$i])];
    } elseif ($tag == TAG_INTEGER_EXT) {
        list(, $value) = unpack('N', substr($data, $i, 4));
        if ($value & 0x80000000) {
            $value = -2147483648 + ($value & 0x7fffffff);
        }

        return [$i + 4, $value];
    } else {
        throw new ParseException('invalid integer tag');
    }
}

function _binary_to_pid($i, $data)
{
    $tag = ord($data[$i]);
    $i += 1;
    if ($tag == TAG_PID_EXT) {
        list($i, $node) = _binary_to_atom($i, $data);
        $id             = substr($data, $i, 4);
        $i += 4;
        $serial = substr($data, $i, 4);
        $i += 4;
        $creation = $data[$i];
        $i += 1;

        return [$i, new OtpErlangPid($node, $id, $serial, $creation)];
    } else {
        throw new ParseException('invalid pid tag');
    }
}

function _binary_to_atom($i, $data)
{
    $tag = ord($data[$i]);
    $i += 1;
    switch ($tag) {
        case TAG_ATOM_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $i += 2;

            return [$i + $j, new OtpErlangAtom(substr($data, $i, $j))];
        case TAG_ATOM_CACHE_REF:
            return [$i + 1, new OtpErlangAtom(ord($data[$i]))];
        case TAG_SMALL_ATOM_EXT:
            $j = ord($data[$i]);
            $i += 1;

            return [$i + $j, new OtpErlangAtom(substr($data, $i, $j))];
        case TAG_ATOM_UTF8_EXT:
            list(, $j) = unpack('n', substr($data, $i, 2));
            $i += 2;

            return [$i + $j,
                         new OtpErlangAtom(substr($data, $i, $j), true), ];
        case TAG_SMALL_ATOM_UTF8_EXT:
            $j = ord($data[$i]);
            $i += 1;

            return [$i + $j,
                         new OtpErlangAtom(substr($data, $i, $j), true), ];
        default:
            throw new ParseException('invalid atom tag');
    }
}

function _term_to_binary($term)
{
    if (is_string($term)) {
        return _string_to_binary($term);
    } elseif (is_array($term)) {
        return _tuple_to_binary($term);
    } elseif (is_int($term)) {
        return _integer_to_binary($term);
    } elseif (is_float($term)) {
        return _float_to_binary($term);
    } elseif (is_bool($term)) {
        if ($term) {
            $object = new OtpErlangAtom('true');
        } else {
            $object = new OtpErlangAtom('false');
        }

        return $object->binary();
    } elseif (is_object($term)) {
        switch (get_class($term)) {
            case __NAMESPACE__.'\OtpErlangAtom':
            case __NAMESPACE__.'\OtpErlangList':
            case __NAMESPACE__.'\OtpErlangBinary':
            case __NAMESPACE__.'\OtpErlangFunction':
            case __NAMESPACE__.'\OtpErlangReference':
            case __NAMESPACE__.'\OtpErlangPort':
            case __NAMESPACE__.'\OtpErlangPid':
            case __NAMESPACE__.'\OtpErlangMap':
                return $term->binary();
            default:
                throw new OutputException('unknown php object');
        }
    } else {
        throw new OutputException('unknown php type');
    }
}

function _string_to_binary($term)
{
    $arity = strlen($term);
    if ($arity == 0) {
        return chr(TAG_NIL_EXT);
    } elseif ($arity < 65536) {
        return pack('Cn', TAG_STRING_EXT, $arity).$term;
    } else {
        $term_packed = '';
        foreach (str_split($term) as $c) {
            $term_packed .= chr(TAG_SMALL_INTEGER_EXT).$c;
        }

        return pack('CN', TAG_LIST_EXT, $arity).$term_packed.
               chr(TAG_NIL_EXT);
    }
}

function _tuple_to_binary($term)
{
    $arity       = count($term);
    $term_packed = '';
    foreach ($term as $element) {
        $term_packed .= _term_to_binary($element);
    }
    if ($arity < 256) {
        return pack('CC', TAG_SMALL_TUPLE_EXT, $arity).$term_packed;
    } else {
        return pack('CN', TAG_LARGE_TUPLE_EXT, $arity).$term_packed;
    }
}

function _integer_to_binary($term)
{
    if (0 <= $term and $term <= 255) {
        return pack('CC', TAG_SMALL_INTEGER_EXT, $term);
    } elseif (-2147483648 <= $term and $term <= 2147483647) {
        return pack('CN', TAG_INTEGER_EXT, $term);
    } else {
        return _bignum_to_binary($term);
    }
}

function _bignum_to_binary($term)
{
    // in PHP only for supporting integers > 32 bits (no native bignums)
    $bignum = abs($term);
    $size   = intval(ceil(_bignum_bit_length($bignum) / 8.0));
    if ($term < 0) {
        $sign = chr(1);
    } else {
        $sign = chr(0);
    }
    $l = $sign;
    if ($size > 0) {
        foreach (range(0, $size - 1) as $byte) {
            $l .= chr($bignum & 255);
            $bignum >>= 8;
        }
    }
    if ($size < 256) {
        return pack('CC', TAG_SMALL_BIG_EXT, $size).$l;
    } else {
        return pack('CN', TAG_LARGE_BIG_EXT, $size).$l;
    }
}

function _bignum_bit_length($bignum)
{
    return strlen(decbin($bignum));
}

function _float_to_binary($term)
{
    if (unpack('S', "\x01\x00") == [1 => 1]) { // little endian
        return chr(TAG_NEW_FLOAT_EXT).strrev(pack('d', $term));
    } else {
        return chr(TAG_NEW_FLOAT_EXT).pack('d', $term);
    }
}

class ParseException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class InputException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class OutputException extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
