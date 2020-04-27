<?php

namespace Zhukmax\SimpleRouter;

use ReflectionException;
use ReflectionMethod;

/**
 * Class Request
 * @package Zhukmax\SimpleRouter
 */
class Request
{
    /**
     * @param string $name
     * @return string
     */
    public static function get(string $name): string
    {
        return trim(str_replace(['\'', '"'], ['\\\'', '\\"'], $_REQUEST[$name])) ?? null;
    }

    /**
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public static function getBool(string $name, bool $default = false): bool
    {
        return (bool)$_REQUEST[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param int|null $min
     * @param int|null $max
     * @param int $default
     * @return int
     */
    public static function getInt(string $name, int $min = null, int $max = null, int $default = 0): int
    {
        $options = [
            'options' => [
                'default' => $default,
                'min_range' => $min ?: -1 * pow(10, 10),
                'max_range' => $max ?: pow(10, 10)
            ],
            'flags' => FILTER_FLAG_ALLOW_OCTAL
        ];
        
        return (int)filter_var(self::get($name), FILTER_VALIDATE_INT, $options);
    }
    
    /**
     * @param string $name
     * @return string
     */
    public static function getEmail(string $name): string
    {
        return filter_var(self::get($name), FILTER_VALIDATE_EMAIL) ?: '';
    }

    /**
     * @param string $class
     * @param string $method
     * @return array
     * @throws ReflectionException
     */
    public static function getArgs(string $class, string $method): array
    {
        $args = [];
        $reflection = new ReflectionMethod($class, $method);

        foreach($reflection->getParameters() AS $arg) {
            $args[$arg->name] = self::getArgByType($arg);
        }

        return $args;
    }

    /**
     * @param \ReflectionParameter $arg
     * @return bool|int|mixed|string|null
     */
    private static function getArgByType(\ReflectionParameter $arg)
    {
        try {
            $default = $arg->getDefaultValue();
        } catch (ReflectionException $e) {
            $default = null;
        }

        switch ($arg->getType()->getName()) {
            case 'string':
                return self::get($arg->getName()) ?? $default;

            case 'int':
                return self::getInt($arg->getName(), null, null, $default);

            case 'bool':
                return self::getBool($arg->getName(), $default ?? false);

            default:
                return self::get($arg->getName()) ?? $default;
        }
    }
}
