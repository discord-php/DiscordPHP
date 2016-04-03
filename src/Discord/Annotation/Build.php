<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Annotation;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Build
{
    /**
     * The property that this is set from, from the API
     *
     * @var string
     */
    public $property;

    /**
     * If set, this is used to build the property
     *
     * @var mixed
     */
    public $class;

    /**
     * If set, used for casting
     *
     * @var mixed
     */
    public $type;

    /**
     * If true, fetch the object by the given ID
     *
     * @var bool
     */
    public $isId;

    /**
     * Build constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $values['property'] = $values['value'];
        }

        $this->property = isset($values['property']) ? $values['property'] : null;
        $this->class    = isset($values['class']) ? $values['class'] : null;
        $this->type     = isset($values['type']) ? $values['type'] : (isset($values['class']) ? 'model' : 'string');
        $this->isId     = isset($values['isId']) ? $values['isId'] : false;
    }
}
