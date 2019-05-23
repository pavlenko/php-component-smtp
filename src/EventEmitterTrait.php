<?php

namespace PE\Component\SMTP;

trait EventEmitterTrait
{
    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     *
     * @return bool
     */
    public function attachListener(string $event, callable $listener, int $priority = 0): bool
    {
        if (empty($this->listeners[$event][$priority])) {
            $this->listeners[$event][$priority] = [];
        }

        if (!in_array($listener, $this->listeners[$event][$priority], true)) {
            $this->listeners[$event][$priority][] = $listener;
            return true;
        }

        return false;
    }

    /**
     * @param string   $event
     * @param callable $listener
     *
     * @return bool If listener detached
     */
    public function detachListener(string $event, callable $listener): bool
    {
        $detached = false;

        if (empty($this->listeners[$event])) {
            return $detached;
        }

        foreach ($this->listeners[$event] as $priority => $listeners) {
            foreach ($listeners as $k => $v) {
                if ($v === $listener) {
                    $detached = true;
                    unset($listeners[$k]);
                } else {
                    $listeners[$k] = $v;
                }
            }

            if ($listeners) {
                $this->listeners[$event][$priority] = $listeners;
            } else {
                unset($this->listeners[$event][$priority]);
            }
        }

        return $detached;
    }

    /**
     * @param string $event
     * @param mixed  ...$arguments
     *
     * @return int Count of executed listeners
     */
    public function trigger(string $event, ...$arguments): int
    {
        $count = 0;

        if (empty($this->listeners[$event])) {
            return $count;
        }

        ksort($this->listeners[$event]);

        foreach ($this->listeners[$event] as $listeners) {
            foreach ($listeners as $listener) {
                $count++;

                $result = $listener(...$arguments);

                if (false === $result) {
                    // For stop event propagation listener must return FALSE
                    return $count;
                }
            }
        }

        return $count;
    }
}
