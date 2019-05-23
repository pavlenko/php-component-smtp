<?php

namespace PE\Component\SMTP;

use PE\Component\SMTP\Exception\ConnectionException;

interface ConnectionInterface
{
    /**
     * @return string
     */
    public function getHost(): string;

    /**
     * @return int
     */
    public function getPort(): int;

    /**
     * @return bool
     */
    public function hasSecurity(): bool;

    /**
     * @return bool
     */
    public function getValidate(): bool;

    /**
     * @throws ConnectionException
     */
    public function open(): void;

    /**
     * @return bool
     */
    public function hasEncryption(): bool;

    /**
     * @param int|null $flags
     */
    public function setEncryption(?int $flags): void;

    /**
     * Write data to socket
     *
     * @param string $data
     *
     * @return int
     *
     * @throws ConnectionException
     */
    public function send(string $data): int;

    /**
     * Read data from socket and decode to Response instance
     *
     * @param int|null $timeout
     *
     * @return ResponseInterface
     *
     * @throws ConnectionException
     */
    public function read(int $timeout = null): ResponseInterface;

    /**
     * Close connection socket
     */
    public function exit(): void;
}
