<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

/**
 * Builds a multipart request.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Multipart
{
    /**
     * The boundary seperating multipart sections.
     */
    private const BOUNDARY = '----DiscordPHPSendFileBoundary';

    /**
     * Fields part of the request.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Field boundary.
     *
     * @var string
     */
    protected $boundary;

    /**
     * Multipart constructor.
     *
     * @param array $fields
     */
    public function __construct(array $fields = [], string $boundary = self::BOUNDARY)
    {
        $this->fields = $fields;
        $this->boundary = $boundary;
    }

    /**
     * Adds a field to the request.
     *
     * ```php
     * $field = [
     *     'name' => 'Field name',
     *     'content' => 'Field content',
     *
     *      // Optional
     *     'filename' => 'File name',
     *     'headers' => [
     *         // ...
     *     ],
     * ];
     * ```
     *
     * @param  array $field
     * @return self
     */
    public function add(...$fields): self
    {
        foreach ($fields as $field) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Gets the headers for the given request.
     *
     * @return array
     */
    public function getHeaders()
    {
        return [
            'Content-Type' => $this->getContentType(),
            'Content-Length' => strlen((string) $this),
        ];
    }

    /**
     * Gets the content type for the multipart request.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'multipart/form-data; boundary='.substr($this->boundary, 2);
    }

    /**
     * Converts the multipart request to string.
     *
     * @return string
     */
    public function __toString()
    {
        $body = '';

        foreach ($this->fields as $field) {
            $body .= $this->boundary."\n";
            $body .= "Content-Disposition: form-data; name={$field['name']}";

            if (isset($field['filename'])) {
                $body .= "; filename={$field['filename']}";
            }

            $body .= "\n";

            if (isset($field['headers'])) {
                foreach ($field['headers'] as $header => $value) {
                    $body .= $header.': '.$value."\n";
                }
            }

            $body .= "\n".$field['content']."\n";
        }

        $body .= $this->boundary."--\n";

        return $body;
    }
}
