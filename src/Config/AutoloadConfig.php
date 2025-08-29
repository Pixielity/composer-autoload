<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Config;

use InvalidArgumentException;
use Modules\ComposerAutoload\Interfaces\ConfigInterface;

/**
 * Class AutoloadConfig
 *
 * Configuration management class for autoloader settings
 */
class AutoloadConfig implements ConfigInterface
{
    /**
     * @var array Configuration data
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param  array  $config  Initial configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Load configuration from a file
     */
    public function loadFromFile(string $configPath): self
    {
        if (! file_exists($configPath)) {
            throw new InvalidArgumentException("Configuration file not found: {$configPath}");
        }

        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        switch (strtolower($extension)) {
            case 'json':
                $data = json_decode(file_get_contents($configPath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException("Invalid JSON in configuration file: {$configPath}");
                }
                break;

            case 'php':
                $data = include $configPath;
                if (! is_array($data)) {
                    throw new InvalidArgumentException("PHP configuration file must return an array: {$configPath}");
                }
                break;

            default:
                throw new InvalidArgumentException("Unsupported configuration file format: {$extension}");
        }

        $this->merge($data);

        return $this;
    }

    /**
     * Set configuration value
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): self
    {
        $this->setNestedValue($this->config, $key, $value);

        return $this;
    }

    /**
     * Get configuration value
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        return $this->getNestedValue($this->config, $key) !== null;
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Merge configuration
     */
    public function merge(array $config): self
    {
        $this->config = array_merge_recursive($this->config, $config);

        return $this;
    }

    /**
     * Get PSR-4 namespaces from config
     */
    public function getPsr4Namespaces(): array
    {
        return $this->get('autoload.psr-4', []);
    }

    /**
     * Get classmap from config
     */
    public function getClassmap(): array
    {
        return $this->get('autoload.classmap', []);
    }

    /**
     * Get files to include from config
     */
    public function getFiles(): array
    {
        return $this->get('autoload.files', []);
    }

    /**
     * Set nested value using dot notation
     *
     * @param  mixed  $value
     */
    protected function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (! isset($current[$k]) || ! is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Get nested value using dot notation
     *
     * @param  mixed  $default
     * @return mixed
     */
    protected function getNestedValue(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (! isset($current[$k])) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }
}
