<?php

namespace PE\Component\SMTP;

interface EventEmitterInterface
{
    const EVENT_PRIORITY_HIGHEST = -1024;
    const EVENT_PRIORITY_LOWEST  = 1024;

    /**
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     *
     * @return bool
     */
    public function attachListener(string $event, callable $listener, int $priority = 0): bool;

    /**
     * @param string   $event
     * @param callable $listener
     *
     * @return bool
     */
    public function detachListener(string $event, callable $listener): bool ;

    /**
     * @param string $event
     * @param mixed  ...$arguments
     *
     * @return int
     */
    public function trigger(string $event, ...$arguments): int;
}
