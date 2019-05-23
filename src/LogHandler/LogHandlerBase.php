<?php

namespace PE\Component\SMTP\LogHandler;

abstract class LogHandlerBase implements LogHandlerInterface
{
    /**
     * @param string $message
     *
     * @return string
     */
    protected function send(string $message): string
    {
        return "--> {$message}\n";
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function read(string $message): string
    {
        return ' <-- ' . str_replace("\n", "\n<-- ", $message) . "\n";
    }
}