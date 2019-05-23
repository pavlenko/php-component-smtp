<?php

namespace PE\Component\SMTP;

use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Module\ModuleInterface;

interface ClientInterface
{
    public const COMMAND_CREATE  = 'command-create';
    public const COMMAND_REQUEST = 'command-request';
    public const COMMAND_SUCCESS = 'command-success';
    public const COMMAND_FAILURE = 'command-failure';

    public const MESSAGE_CREATE  = 'message-create';
    public const MESSAGE_REQUEST = 'message-request';
    public const MESSAGE_SUCCESS = 'message-success';
    public const MESSAGE_FAILURE = 'message-failure';

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface;

    /**
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection): void;

    /**
     * @param ModuleInterface $module
     */
    public function attachModule(ModuleInterface $module): void;

    /**
     * @param ModuleInterface $module
     */
    public function detachModule(ModuleInterface $module): void;

    /**
     * @throws ExceptionInterface
     */
    public function HELO(): void;

    /**
     * @param string $from
     *
     * @throws ExceptionInterface
     */
    public function MAIL(string $from);

    /**
     * @param string $addr
     *
     * @throws ExceptionInterface
     */
    public function RCPT(string $addr);

    /**
     * @param string $data
     *
     * @throws ExceptionInterface
     */
    public function DATA(string $data);

    /**
     * @throws ExceptionInterface
     */
    public function RSET();

    /**
     * @param string $user
     *
     * @throws ExceptionInterface
     */
    public function VRFY(string $user);

    /**
     * @throws ExceptionInterface
     */
    public function NOOP();

    /**
     * @throws ExceptionInterface
     */
    public function QUIT();

    /**
     * @param Command  $command
     * @param int[]    $codes
     * @param int|null $timeout
     *
     * @return ResponseInterface|null
     *
     * @throws ExceptionInterface
     */
    public function sendCommand(Command $command, array $codes, int $timeout = null): ?ResponseInterface;

    /**
     * @param string     $message
     * @param array|null $codes
     * @param int|null   $timeout
     *
     * @return ResponseInterface|null
     *
     * @throws ExceptionInterface
     */
    public function sendMessage(string $message, array $codes = null, int $timeout = null): ?ResponseInterface;

    /**
     * @param Command  $command
     * @param array    $codes
     * @param int|null $timeout
     *
     * @return ResponseInterface
     *
     * @throws ExceptionInterface
     */
    public function readCommandResponse(Command $command, array $codes, int $timeout = null): ResponseInterface;

    /**
     * @param string   $message
     * @param array    $codes
     * @param int|null $timeout
     *
     * @return ResponseInterface
     *
     * @throws ExceptionInterface
     */
    public function readMessageResponse(string $message, array $codes, int $timeout = null): ResponseInterface;
}
