<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Erlpack;

use Discord\Helpers\OtpErlangAtom as Atom;
use Discord\Helpers\OtpErlangBinary as Binary;
use Discord\Helpers\OtpErlangList as EList;
use Discord\Helpers\OtpErlangMap as Map;
use Evenement\EventEmitter;

/**
 * Encodes and decodes Erlang External Term Format.
 */
class Erlpack extends EventEmitter
{
    /**
     * Creates a new erlpack instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Nothing for now.
    }

    /**
     * Packs a PHP array into Erlang ETF format.
     *
     * @param array $contents The PHP array to pack.
     *
     * @return binary The packed ETF.
     */
    public function pack(array $contents = [])
    {
        try {
            return \Discord\Helpers\term_to_binary($this->arrayToMap($contents));
        } catch (\Exception $e) {
            $this->emit('error', [$e]);
        }
    }

    /**
     * Unpacks packed Erlang ETF.
     *
     * @param binary $etf The packed ETF to unpack.
     *
     * @return string The unpacked ETF.
     */
    public function unpack($etf)
    {
        return $this->mapToArray(\Discord\Helpers\binary_to_term($etf));
    }

    /**
     * Converts a PHP array to Erlang ETF map.
     *
     * @param array $array The array to convert
     *
     * @return Map The converted map.
     */
    public function arrayToMap(array $array = [])
    {
        $contents = [];

        foreach ($array as $key => $value) {
            $key = new Binary($key);

            if (is_string($value)) {
                $value = new Binary($value);
            } elseif (is_array($value)) {
                if ($this->isAssoc($value)) {
                    $value = $this->arrayToMap($value);
                } else {
                    $value = new EList($value);
                }
            } elseif (is_bool($value)) {
                $value = new Atom(($value) ? 'true' : 'false');
            } elseif (is_null($value)) {
                $value = new Atom('nil');
            }

            $contents[] = [$key, $value];
        }

        return new Map($contents);
    }

    /**
     * Converts an Erlang ETF map to a PHP array.
     *
     * @param Map $map The map to convert.
     *
     * @return array The converted array.
     */
    public function mapToArray(Map $map)
    {
        $contents = [];

        foreach ($map->pairs as $pair) {
            list($key, $value) = $pair;

            if ($key instanceof Atom) {
                $key = $key->value;
            }

            $key = $this->convertItem($key);

            if (! is_string($key)) {
                $key = $key->value;
            }

            $contents[$key] = $this->convertItem($value);
        }

        return $contents;
    }

    /**
     * Converts an Erlang ETF list to a PHP array.
     *
     * @param EList $list The list to convert
     *
     * @return array The converted array.
     */
    public function listToArray(EList $list)
    {
        $contents = [];

        foreach ($list->value as $value) {
            $contents[] = $this->convertItem($value);
        }

        return $contents;
    }

    /**
     * Converts an Erlang ETF atom to a PHP value.
     *
     * @param Atom $atom The atom to convert
     *
     * @return mixed The converted value.
     */
    public function atomToValue(Atom $atom)
    {
        if ($atom->value == 'nil' ||
            $atom->value == 'null') {
            return;
        } elseif ($atom->value == 'true') {
            return true;
        } elseif ($atom->value == 'false') {
            return false;
        } else {
            return $atom->value;
        }
    }

    /**
     * Detects and converts an Erlang ETF type to a PHP value.
     *
     * @param mixed $item The Erlang ETF type to convert.
     *
     * @return mixed The converted value.
     */
    protected function convertItem($item)
    {
        if ($item instanceof Atom) {
            return $this->atomToValue($item);
        } elseif ($item instanceof Binary) {
            return $item->value;
        } elseif ($item instanceof EList) {
            return $this->listToArray($item);
        } elseif ($item instanceof Map) {
            return $this->mapToArray($item);
        } else {
            return $item;
        }
    }

    /**
     * Checks if an array is associative.
     *
     * @param array $array The array to check.
     *
     * @return bool Whether the array is associative.
     */
    protected function isAssoc(array $array = [])
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
