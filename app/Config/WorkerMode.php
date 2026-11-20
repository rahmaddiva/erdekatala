<?php

namespace Config;

/**
 * This configuration controls how CodeIgniter behaves when running
 * in worker mode (with FrankenPHP).
 */
class WorkerMode
{
    /**
     * Persistent Services
     *
     * List of service names that should persist across requests.
     * These services will NOT be reset between requests.
     *
     * @var list<string>
     */
    public array $persistentServices = [
        'autoloader',
        'locator',
        'exceptions',
        'commands',
        'codeigniter',
        'superglobals',
        'routes',
        'cache',
    ];

    /**
     * Reset Event Listeners
     *
     * @var list<string>
     */
    public array $resetEventListeners = [];

    /**
     * Force Garbage Collection
     */
    public bool $forceGarbageCollection = true;
}
