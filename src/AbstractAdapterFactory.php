<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

use Laminas\Db\Paginator\Adapter\Exception\InvalidArgumentException;
use Laminas\Db\Paginator\Adapter\Exception\UnexpectedValueException;
use ReflectionClass;
use ReflectionException;

abstract class AbstractAdapterFactory
{
    /**
     * @param class-string $baseAdapterClass
     * @param class-string $requestedName
     * @param array|null   $options
     * @throws ReflectionException
     * @return Select
     */
    protected function buildAdapter(
        string $baseAdapterClass,
        string $requestedName,
        ?array $options = null
    ): Select {
        // Validate options
        if ($options === null) {
            throw new InvalidArgumentException('Missing options');
        }

        // Validate that requestedName is a real class
        if (! class_exists($requestedName)) {
            throw new InvalidArgumentException(sprintf(
                'The requested class %s does not exist.',
                $requestedName
            ));
        }

        // Ensure that the requested name is a subclass or the base class itself
        if (! is_subclass_of($requestedName, $baseAdapterClass) && $requestedName !== $baseAdapterClass) {
            throw new InvalidArgumentException(sprintf(
                'The requested class %s is not a valid subclass of %s.',
                $requestedName,
                $baseAdapterClass
            ));
        }

        // Reflect to verify constructor compatibility with options
        $classReflection = new ReflectionClass($requestedName);

        // Check if constructor exists
        $constructor = $classReflection->getConstructor();

        // Ensure options match the constructor arguments, if a constructor exists
        if ($constructor !== null) {
            $parameters = $constructor->getParameters();

            // Check parameter count
            if (count($parameters) !== count($options)) {
                throw new InvalidArgumentException(sprintf(
                    'Constructor of %s expects %d arguments, %d given.',
                    $requestedName,
                    count($parameters),
                    count($options)
                ));
            }
        }

        // Instantiate the class
        $instance = $classReflection->newInstanceArgs($options);

        // Validate the instance type
        if (! ($instance instanceof Select)) {
            throw new UnexpectedValueException(sprintf(
                'Expected instance of %s, got %s instead.',
                $baseAdapterClass,
                get_class($instance)

            ));
        }

        return $instance;
    }
}