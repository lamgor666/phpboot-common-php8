<?php

namespace phpboot\common\util;

use phpboot\common\Cast;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;

final class ReflectUtils
{
    private function __construct()
    {
    }

    public static function getClassAnnotation(ReflectionClass $refClazz, string $annoClass): ?object
    {
        try {
            $annotations = $refClazz->getAttributes();
        } catch (Throwable) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (str_contains($clazz, $annoClass)) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    public static function getMethodAnnotation(ReflectionMethod $method, string $annoClass): ?object
    {
        try {
            $annotations = $method->getAttributes();
        } catch (Throwable) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (str_contains($clazz, $annoClass)) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @param bool $strictMode
     * @return ReflectionMethod|null
     */
    public static function getGetter(
        ReflectionProperty $property,
        array $methods = [],
        bool $strictMode = false
    ): ?ReflectionMethod
    {
        $fieldType = $property->getType();
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $getter = null;

        foreach ($methods as $method) {
            $returnType = $method->getReturnType();

            if ($strictMode) {
                if (!($fieldType instanceof ReflectionNamedType) ||
                    !($returnType instanceof ReflectionNamedType) ||
                    $returnType->getName() !== $fieldType->getName()) {
                    continue;
                }
            }

            if (strtolower($method->getName()) === "get$fieldName") {
                $getter = $method;
                break;
            }

            $s1 = StringUtils::ensureLeft($fieldName, 'is');
            $s2 = StringUtils::ensureLeft(strtolower($method->getName()), 'is');

            if ($s1 === $s2) {
                $getter = $method;
                break;
            }
        }

        return $getter;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @param bool $strictMode
     * @return ReflectionMethod|null
     */
    public static function getSetter(
        ReflectionProperty $property,
        array $methods = [],
        bool $strictMode = false
    ): ?ReflectionMethod
    {
        $fieldType = $property->getType();
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $setter = null;

        foreach ($methods as $method) {
            try {
                $args = $method->getParameters();
            } catch (Throwable) {
                $args = [];
            }

            if (count($args) !== 1) {
                continue;
            }

            $argType = $args[0]->getType();

            if ($strictMode) {
                if (!($fieldType instanceof ReflectionNamedType) ||
                    !($argType instanceof ReflectionNamedType) ||
                    $argType->getName() !== $fieldType->getName()) {
                    continue;
                }
            }

            if (strtolower($method->getName()) === "set$fieldName") {
                $setter = $method;
                break;
            }
        }

        return $setter;
    }

    public static function getPropertyAnnotation(ReflectionProperty $property, string $annoClass): ?object
    {
        try {
            $annotations = $property->getAttributes();
        } catch (Throwable) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (str_contains($clazz, $annoClass)) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    public static function getParameterAnnotation(ReflectionParameter $param, string $annoClass): ?object
    {
        try {
            $annotations = $param->getAttributes();
        } catch (Throwable) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            $clazz = StringUtils::ensureLeft($anno->getName(), "\\");

            if (str_contains($clazz, $annoClass)) {
                return self::buildAnno($anno);
            }
        }

        return null;
    }

    public static function getMapKeyByProperty(ReflectionProperty $property, array $propertyNameToMapKey = []): string
    {
        try {
            $annotations = $property->getAttributes();
        } catch (Throwable) {
            $annotations = [];
        }

        if (!is_array($annotations)) {
            $annotations = [];
        }

        $annoMapKey = null;

        foreach ($annotations as $anno) {
            $annoClass = $anno->getName();

            if (!is_string($annoClass) || $annoClass === '') {
                continue;
            }

            if (StringUtils::endsWith($annoClass, 'MapKey')) {
                $annoMapKey = $anno;
                break;
            }
        }

        if (is_object($annoMapKey) && method_exists($annoMapKey, 'getValue')) {
            $mapKey = Cast::toString($annoMapKey->getValue());

            if ($mapKey !== '') {
                return $mapKey;
            }
        }

        $fieldName = $property->getName();

        if (!is_string($fieldName) || $fieldName === '') {
            return '';
        }

        $mapKey = Cast::toString($propertyNameToMapKey[$fieldName]);
        return $mapKey === '' ? $fieldName : $mapKey;
    }

    public static function getMapValueByProperty(
        array $map1,
        ReflectionProperty $property,
        array $propertyNameToMapKey = []
    )
    {
        if (empty($map1)) {
            return null;
        }

        $mapKey = self::getMapKeyByProperty($property, $propertyNameToMapKey);
        $mapKey = strtolower(strtr($mapKey, ['-' => '', '_' => '']));

        if (empty($mapKey)) {
            return null;
        }

        foreach ($map1 as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $key = strtolower(strtr($key, ['-' => '', '_' => '']));

            if ($key === $mapKey) {
                return $val;
            }

            if (StringUtils::ensureLeft($key, 'is') === StringUtils::ensureLeft($mapKey, 'is')) {
                return $val;
            }
        }

        return null;
    }

    private static function buildAnno($arg0): ?object
    {
        if (!is_object($arg0) || !method_exists($arg0, 'getName') || !method_exists($arg0, 'getArguments')) {
            return null;
        }

        try {
            $className = StringUtils::ensureLeft($arg0->getName(), "\\");
            $clazz = new ReflectionClass($className);
            $arguments = $arg0->getArguments();

            if (is_array($arguments) && !empty($arguments)) {
                $anno = $clazz->newInstance(...$arguments);
            } else {
                $anno = $clazz->newInstance();
            }
        } catch (Throwable) {
            $anno = null;
        }

        return is_object($anno) ? $anno : null;
    }
}
