<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Manager;

use Discord\Annotation\Build;
use Discord\Model\AbstractModel;
use Discord\Model\IdentifierModelInterface;
use Discord\Repository\AbstractRepository;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var AbstractRepository
     */
    protected $repository;

    /**
     * AbstractManager constructor.
     *
     * @param ContainerInterface $container
     *
     * @internal param Http $http
     * @internal param Discord $discord
     * @internal param CacheWrapper $cacheWrapper
     * @internal param Reader $reader
     * @internal param AbstractRepository $repository
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->reader    = $container->get('annotation_reader');

        if ($container->has('repository.'.$this->getBaseName())) {
            $this->repository = $container->get('repository.'.$this->getBaseName());
        }
    }

    /**
     * Returns a model class name.
     *
     * @return string
     */
    abstract public function getModel();

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getIdFromData(array $data)
    {
        return $data['id'];
    }

    /**
     * @param array $data
     * @param bool  $complete Is the model built completely
     *
     * @throws \Exception
     *
     * @return AbstractModel
     */
    public function create($data, $complete = true)
    {
        // Deep convert objects to arrays
        $data = $data instanceof \stdClass ? json_decode(json_encode($data), true) : $data;

        $cls   = $this->getModel();
        $model = new $cls();

        if (!($model instanceof AbstractModel)) {
            throw new \Exception('getModel must return a class that extends Discord\\Model\\AbstractModel.');
        }

        // This feels hacky. But its working for now.
        if ($model instanceof IdentifierModelInterface) {
            if ($this->repository !== null && $this->repository->hasKey($this->getIdFromData($data))) {
                return $this->repository->get($this->getIdFromData($data));
            }
        }

        $expressionLanguage = new ExpressionLanguage();

        $refClass   = new \ReflectionObject($model);
        $properties = $refClass->getProperties();
        foreach ($properties as $property) {
            /** @var Build $annotation */
            $annotation = $this->reader->getPropertyAnnotation($property, Build::class);
            if (empty($annotation) || !is_array($data)) {
                continue;
            }

            try {
                @$value = $expressionLanguage->evaluate($annotation->property, $data);
            } catch (\RuntimeException $e) {
                continue;
            } catch (SyntaxError $e) {
                continue;
            }

            $property->setAccessible(true);

            /*
             * If the annotation doesn't have a class, its not a reference, so just cast it
             */
            if ($annotation->class === null) {
                $property->setValue($model, $this->castValue($annotation->type, $value));
            } else {
                /*
                 * If the annotation does have a class, lets create the reference.
                 *
                 * If its an array, build all the references rom the array.
                 * If its not an array, but it is an ID, lets build a shell class, that is set to built=false
                 *
                 * Otherwise, just create the reference.
                 */

                $subClass = $annotation->class;
                if ($annotation->type !== 'array') {
                    if ($annotation->isId) {
                        $subModel = $this->getManager($subClass)->create(['id' => $value], false);

                        $property->setValue($model, $subModel);
                    } else {
                        if ($subClass === 'DateTime') {
                            $property->setValue($model, new \DateTimeImmutable($value));
                        } else {
                            $property->setValue($model, $this->getManager($subClass)->create($value));
                        }
                    }
                } else {
                    $values = array_map(
                        function ($value) use ($annotation, $subClass) {
                            if ($annotation->isId) {
                                return $this->getManager($subClass)->create(['id' => $value], false);
                            }

                            return $this->getManager($subClass)->create($value);
                        },
                        $value
                    );
                    $property->setValue($model, new ArrayCollection($values));
                }
            }
            $property->setAccessible(false);
        }

        $model->setBuilt($complete);

        if ($this->repository !== null) {
            $this->repository->add($model);
        }

        return $model;
    }

    /**
     * @param AbstractModel $model
     */
    protected function addToRepository(AbstractModel $model)
    {
        if ($this->repository === null) {
            return;
        }

        $this->repository->add($model);
    }

    /**
     * @param string $class
     *
     * @return AbstractManager
     */
    protected function getManager($class)
    {
        return $this->container->get('manager.'.$this->getBaseName($class));
    }

    /**
     * Casts value to $type, if not null.
     *
     * @param string|null $type
     * @param mixed       $value
     *
     * @return mixed
     */
    private function castValue($type, $value)
    {
        if ($type === null) {
            return $value;
        }

        if ($type === 'array') {
            if (!is_array($value)) {
                return (array) $value;
            }

            return $value;
        }

        if ($type === 'string') {
            return strval($value);
        }

        $method = $type.'val';

        return $method($value);
    }

    /**
     * @param string|null $model
     *
     * @return string
     */
    private function getBaseName($model = null)
    {
        $model = $model === null ? $this->getModel() : $model;

        return lcfirst(str_replace('Discord\Model\\', '', $model));
    }
}
