<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Interfaces;

/**
 * Interface ConfigInterface
 *
 * Interface for managing autoloader configuration
 */
interface ConfigInterface
{
    /**
     * Load configuration from a file
     */
    public function loadFromFile(string $configPath): self;

    /**
     * Set configuration value
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): self;

    /**
     * Get configuration value
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool;

    /**
     * Get all configuration
     */
    public function all(): array;

    /**
     * Merge configuration
     */
    public function merge(array $config): self;

    /**
     * Get PSR-4 namespaces from config
     */
    public function getPsr4Namespaces(): array;

    /**
     * Get classmap from config
     */
    public function getClassmap(): array;

    /**
     * Get files to include from config
     */
    public function getFiles(): array;
}
