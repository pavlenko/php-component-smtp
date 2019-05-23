<?php

namespace PE\Component\SMTP;

use PE\Component\SMTP\Exception\ConnectionException;

final class Connection implements ConnectionInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $security;

    /**
     * @var bool
     */
    private $validate;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @param string $host
     * @param int    $port
     * @param bool   $security
     * @param bool   $validate
     */
    public function __construct(string $host = 'localhost', int $port = 25, bool $security = false, bool $validate = true)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->security = $security;
        $this->validate = $validate;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @inheritDoc
     */
    public function hasSecurity(): bool
    {
        return $this->security;
    }

    /**
     * @inheritDoc
     */
    public function getValidate(): bool
    {
        return $this->validate;
    }

    /**
     * @inheritDoc
     */
    public function open(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $errorNum = 0;
        $errorStr = '';

        set_error_handler(static function ($error, $message = '') {
            throw new ConnectionException(sprintf('Could not open socket: %s', $message), $error);
        }, E_WARNING);

        $this->socket = stream_socket_client(
            ($this->security ? 'ssl' : 'tcp') . '://' . $this->host . ':' . $this->port,
            $errorNum,
            $errorStr,
            30,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'verify_peer'      => $this->validate,
                    'verify_peer_name' => $this->validate,
                ]
            ])
        );

        restore_error_handler();

        if ($this->socket === false) {
            if ($errorNum === 0) {
                $errorStr = 'Could not open socket';
            }

            throw new ConnectionException($errorStr);
        }

        if (false === stream_set_timeout($this->socket, 30)) {
            throw new ConnectionException('Could not set stream timeout');
        }
    }

    /**
     * @inheritDoc
     */
    public function hasEncryption(): bool
    {
        return $this->security || isset(stream_get_meta_data($this->socket)['crypto']);
    }

    /**
     * @inheritDoc
     */
    public function setEncryption(?int $flags): void
    {
        if ($this->security) {
            return;
        }

        if (!@stream_socket_enable_crypto($this->socket, null !== $flags, $flags)) {
            throw new ConnectionException('Unable to set connection encryption');
        }
    }

    /**
     * @inheritDoc
     */
    public function send(string $data): int
    {
        if (!is_resource($this->socket)) {
            throw new ConnectionException('No connection has been established');
        }

        if (false === ($result = fwrite($this->socket, $data . "\r\n"))) {
            throw new ConnectionException('Cannot send data');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function read(int $timeout = null): ResponseInterface
    {
        if (!is_resource($this->socket)) {
            throw new ConnectionException('No connection has been established');
        }

        if ($timeout !== null) {
            stream_set_timeout($this->socket, $timeout);
        }

        $response = [];

        do {
            $line = fgets($this->socket);
            $info = stream_get_meta_data($this->socket);

            if ($info['timed_out']) {
                throw new ConnectionException('Connection timed out');
            }

            if (false === $line) {
                throw new ConnectionException('Cannot read data');
            }

            list($code, $message) = preg_split('/([\s-]+)/', $line, 2);

            $response[] = trim($message);
        } while (null !== $line && false !== $line && ' ' !== $line[3]);

        return new Response($code, array_shift($response), $response);
    }

    /**
     * @inheritDoc
     */
    public function exit(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
