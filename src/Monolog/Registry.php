<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog;

use InvalidArgumentException;

use ArrayAccess;

/**
 * Monolog log registry
 *
 * Allows to get `Logger` instances in the global scope
 * via static method calls on this class.
 *
 * <code>
 * $application = new Monolog\Logger('application');
 * $api = new Monolog\Logger('api');
 *
 * Monolog\Registry::addLogger($application);
 * Monolog\Registry::addLogger($api);
 *
 * function testLogger()
 * {
 *     Monolog\Registry::api()->addError('Sent to $api Logger instance');
 *     Monolog\Registry::application()->addError('Sent to $application Logger instance');
 * }
 * </code>
 *
 *
 * You can also use an instance of the registry as if it were an array of
 * logger instances. All instances will reference the same registry.
 *
 * <code>
 * $application = new Monolog\Logger('application');
 * $api = new Monolog\Logger('api');
 *
 * $loggers = new Monolog\Registry;
 *
 * $loggers['api'] = $api;
 * Monolog\Registry::addLogger($application);
 *
 * function logToMultiple($logRegistry)
 * {
 *      $logRegistry['api']->error('Sent to $api Logger instance');
 *      $logRegistry['application']->error('Sent to $application Logger instance');
 * }
 * </code>
 *
 * @author Tomas Tatarko <tomas@tatarko.sk>
 */
class Registry implements ArrayAccess
{
    /**
     * List of all loggers in the registry (by named indexes)
     *
     * @var Logger[]
     */
    private static $loggers = array();

    /**
     * Adds new logging channel to the registry
     *
     * @param  Logger                    $logger    Instance of the logging channel
     * @param  string|null               $name      Name of the logging channel ($logger->getName() by default)
     * @param  boolean                   $overwrite Overwrite instance in the registry if the given name already exists?
     * @throws \InvalidArgumentException If $overwrite set to false and named Logger instance already exists
     */
    public static function addLogger(Logger $logger, $name = null, $overwrite = false)
    {
        $name = $name ?: $logger->getName();

        if (isset(self::$loggers[$name]) && !$overwrite) {
            throw new InvalidArgumentException('Logger with the given name already exists');
        }

        self::$loggers[$name] = $logger;
    }

    /**
     * Checks if such logging channel exists by name or instance
     *
     * @param string|Logger $logger Name or logger instance
     */
    public static function hasLogger($logger)
    {
        if ($logger instanceof Logger) {
            $index = array_search($logger, self::$loggers, true);

            return false !== $index;
        } else {
            return isset(self::$loggers[$logger]);
        }
    }

    /**
     * Removes instance from registry by name or instance
     *
     * @param string|Logger $logger Name or logger instance
     */
    public static function removeLogger($logger)
    {
        if ($logger instanceof Logger) {
            if (false !== ($idx = array_search($logger, self::$loggers, true))) {
                unset(self::$loggers[$idx]);
            }
        } else {
            unset(self::$loggers[$logger]);
        }
    }

    /**
     * Clears the registry
     */
    public static function clear()
    {
        self::$loggers = array();
    }

    /**
     * Gets Logger instance from the registry
     *
     * @param  string                    $name Name of the requested Logger instance
     * @return Logger                    Requested instance of Logger
     * @throws \InvalidArgumentException If named Logger instance is not in the registry
     */
    public static function getInstance($name)
    {
        if (!isset(self::$loggers[$name])) {
            throw new InvalidArgumentException(sprintf('Requested "%s" logger instance is not in the registry', $name));
        }

        return self::$loggers[$name];
    }

    /**
     * Gets Logger instance from the registry via static method call
     *
     * @param  string                    $name      Name of the requested Logger instance
     * @param  array                     $arguments Arguments passed to static method call
     * @return Logger                    Requested instance of Logger
     * @throws \InvalidArgumentException If named Logger instance is not in the registry
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getInstance($name);
    }

    /**
     * Adds new logging channel to the registry, using the Array
     * Access style.
     *
     * This *will* overwrite existing loggers.
     *
     * @param  Logger                    $value     Instance of the logging channel
     * @param  string|null               $offset    Name of the logging channel, or null to append numerically.
     * @throws \InvalidArgumentException If $value is not a Logger.
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof Logger)) {
            throw new InvalidArgumentException('The Monolog Registry only holds Loggers.');
        }

        // append numerically
        if (is_null($offset)) {
            self::$loggers[] = $value;
            return;
        }

        self::addLogger($value, $offset, true);
    }

    /**
     * Checks if such logging channel exists by name, using the Array
     * Access style.
     *
     * @param string $offset Name of the logger instance
     */
    public function offsetExists($offset)
    {
        return isset(self::$loggers[$offset]);
    }

    /**
     * Removes instance from registry by name, using the Array
     * Access style.
     *
     * @param string|Logger $logger Name or logger instance
     */
    public function offsetUnset($offset)
    {
        unset(self::$loggers[$offset]);
    }

    /**
     * Gets Logger instance from the registry, using the Array
     * Access style.
     *
     * @param  string      $offset Name of the requested Logger instance
     * @return Logger|null Requested instance of Logger, or null if it couldn't be found.
     */
    public function offsetGet($offset)
    {
        return isset(self::$loggers[$offset]) ? self::$loggers[$offset] : null;
    }
}
