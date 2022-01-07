<?php

namespace phpboot\common\traits;

use phpboot\common\util\ReflectUtils;
use phpboot\common\util\StringUtils;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

trait MapAbleTrait
{
    public function fromMap(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                unset($data[$key]);
                continue;
            }

            $pname = $key;
            $needUcwords = false;

            if (str_contains($pname, '-')) {
                $pname = str_replace('-', ' ', $pname);
                $needUcwords = true;
            } else if (str_contains($pname, '_')) {
                $pname = str_replace('_', ' ', $pname);
                $needUcwords = true;
            }

            if ($needUcwords) {
                $pname = ucwords($pname);
                $pname = str_replace(' ', '', $pname);
            }

            $pname = lcfirst($pname);
            $isNewValue = false;

            if (is_string($value)) {
                if (str_starts_with($value, '@Duration:')) {
                    $value = StringUtils::toDuration(str_replace('@Duration:', '', $value));
                    $isNewValue = true;
                } else if (str_starts_with($value, '@DataSize:')) {
                    $value = StringUtils::toDataSize(str_replace('@DataSize:', '', $value));
                    $isNewValue = true;
                }
            }

            if ($key !== $pname) {
                $data[$pname] = $value;
                unset($data[$key]);
            } else if ($isNewValue) {
                $data[$key] = $value;
            }
        }

        foreach ($data as $pname => $value) {
            if (!property_exists($this, $pname)) {
                continue;
            }

            try {
                $this->$pname = $value;
            } catch (Throwable) {
            }
        }
    }

    public function toMap(array $propertyNameToMapKey = [], bool $ignoreNull = false): array
    {
        try {
            $clazz = new ReflectionClass(StringUtils::ensureLeft(get_class($this), "\\"));
        } catch (Throwable) {
            $clazz = null;
        }

        if (!($clazz instanceof ReflectionClass)) {
            return [];
        }

        $map1 = [];

        foreach ($clazz->getProperties() as $property) {
            $propertyName = $property->getName();

            if ($propertyName === '') {
                continue;
            }

            try {
                $value = $this->$propertyName;
            } catch (Throwable) {
                continue;
            }

            if ($ignoreNull && $value === null) {
                continue;
            }

            $mapKey = $this->getMapKeyByProperty($property, $propertyNameToMapKey);

            if ($mapKey === '') {
                continue;
            }

            $map1[$mapKey] = $value;
        }

        return $map1;
    }

    private function getMapKeyByProperty(ReflectionProperty $property, array $propertyNameToMapKey = []): string
    {
        return ReflectUtils::getMapKeyByProperty($property, $propertyNameToMapKey);
    }
}
