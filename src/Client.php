<?php

namespace PE\Component\SMTP;

use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\ResponseException;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Module\ModuleInterface;

final class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $name = 'localhost';

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var EventEmitterInterface
     */
    private $dispatcher;

    /**
     * @var bool
     */
    private $enabledAutoQuit = true;

    /**
     * @var ModuleInterface[]
     */
    private $modules = [];

    private $sess = false;
    private $mail = false;
    private $rcpt = false;
    private $data = false;

    /**
     * @param ConnectionInterface        $connection
     * @param EventEmitterInterface|null $dispatcher
     */
    public function __construct(ConnectionInterface $connection, EventEmitterInterface $dispatcher = null)
    {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher ?: new EventEmitter();
    }

    /**
     * @throws ExceptionInterface
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @inheritDoc
     */
    public function attachModule(ModuleInterface $module): void
    {
        $key = spl_object_hash($module);

        if (!isset($this->modules[$key])) {
            $this->modules[$key] = $module;

            foreach ($this->modules[$key]->getClientListeners() as $listener) {
                $this->dispatcher->attachListener(...$listener);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function detachModule(ModuleInterface $module): void
    {
        $key = spl_object_hash($module);

        if (isset($this->modules[$key])) {
            foreach ($this->modules[$key]->getClientListeners() as $listener) {
                $this->dispatcher->detachListener(...$listener);
            }

            unset($this->modules[$key]);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function isEnabledAutoQuit(): bool
    {
        return $this->enabledAutoQuit;
    }

    /**
     * @param bool $enabledAutoQuit
     */
    public function setEnabledAutoQuit(bool $enabledAutoQuit): void
    {
        $this->enabledAutoQuit = $enabledAutoQuit;
    }

    /**
     * @throws ExceptionInterface
     */
    public function connect(): void
    {
        $this->connection->open();
        $this->read([220], 300);
    }

    /**
     * @throws ExceptionInterface
     */
    public function disconnect(): void
    {
        $this->QUIT();
        $this->connection->exit();
    }

    /**
     * @throws ExceptionInterface
     */
    public function HELO(): void
    {
        try {
            $this->sendCommand(new Command('EHLO', [$this->name]), [250], 300);
        } catch (ExceptionInterface $exception) {
            $this->sendCommand(new Command('HELO', [$this->name]), [250], 300);
        }

        $this->sess = true;
    }

    /**
     * @param string $from
     *
     * @throws ExceptionInterface
     */
    public function MAIL(string $from)
    {
        if (!$this->sess) {
            throw new RuntimeException('No session has started');
        }

        $this->sendCommand(new Command('MAIL', ['FROM:<' . $from . '>']), [250], 300);

        $this->mail = true;
        $this->rcpt = false;
        $this->data = false;
    }

    /**
     * @param string $addr
     *
     * @throws ExceptionInterface
     */
    public function RCPT(string $addr)
    {
        if (!$this->mail) {
            throw new RuntimeException('No sender reverse path has been supplied');
        }

        $this->sendCommand(new Command('RCPT', ['TO:<' . $addr . '>']), [250, 251], 300);

        $this->rcpt = true;
    }

    /**
     * @param string $data
     *
     * @throws ExceptionInterface
     */
    public function DATA(string $data)
    {
        if (!$this->rcpt) {
            throw new RuntimeException('No recipient forward path has been supplied');
        }

        $this->sendCommand(new Command('DATA'), [354], 120);

        // @codeCoverageIgnoreStart
        if (($fp = fopen('php://temp', 'rb+')) === false) {
            throw new RuntimeException('Cannot fopen');
        }

        if (fwrite($fp, $data) === false) {
            throw new RuntimeException('Cannot fwrite');
        }
        // @codeCoverageIgnoreEnd

        unset($data);
        rewind($fp);

        // max line length is 998 char + \r\n = 1000
        while (($line = stream_get_line($fp, 1000, "\n")) !== false) {
            $line = rtrim($line, "\r");

            if (0 === strpos($line, '.')) {
                // Escape lines prefixed with a '.'
                $line = '.' . $line;
            }

            $this->sendMessage($line);
        }
        fclose($fp);

        $this->sendMessage('.', [250], 600);

        $this->data = true;
    }

    /**
     * @throws ExceptionInterface
     */
    public function RSET()
    {
        $this->sendCommand(new Command('RSET'), [250, 220]);

        $this->mail = false;
        $this->rcpt = false;
        $this->data = false;
    }

    /**
     * @param string $user
     *
     * @throws ExceptionInterface
     */
    public function VRFY(string $user)
    {
        $this->sendCommand(new Command('VRFY', [$user]), [250, 251, 252], 300);
    }

    /**
     * @throws ExceptionInterface
     */
    public function NOOP()
    {
        $this->sendCommand(new Command('NOOP'), [250], 300);
    }

    /**
     * @throws ExceptionInterface
     */
    public function QUIT()
    {
        if ($this->sess) {
            if ($this->isEnabledAutoQuit()) {
                $this->sendCommand(new Command('QUIT'), [221], 300);
            }

            $this->sess = false;
        }
    }

    /**
     * @inheritDoc
     */
    public function sendCommand(Command $command, array $codes, int $timeout = null): ?ResponseInterface
    {
        $this->dispatcher->trigger(self::COMMAND_CREATE, new EventCommand($command, $this));

        try {
            $this->connection->send((string) $command);
        } catch (ExceptionInterface $exception) {
            $event = new EventCommand($command, $this);
            $event->setException($exception);

            $this->dispatcher->trigger(self::COMMAND_FAILURE, $event);

            throw $exception;
        }

        $event = new EventCommand($command, $this);
        $event->setExpectedCodes($codes);
        $event->setExpectedTimeout($timeout);

        $this->dispatcher->trigger(self::COMMAND_REQUEST, $event);

        if (!$event->isExpectedResponse()) {
            return null;
        }

        return $this->readCommandResponse($command, $codes, $timeout);
    }

    /**
     * @param string     $message
     * @param array|null $codes
     * @param int|null   $timeout
     *
     * @return Response|null
     *
     * @throws ExceptionInterface
     */
    public function sendMessage(string $message, array $codes = null, int $timeout = null): ?ResponseInterface
    {
        $this->dispatcher->trigger(self::MESSAGE_CREATE, new EventMessage($message, $this));

        try {
            $this->connection->send($message);
        } catch (ExceptionInterface $exception) {
            $event = new EventMessage($message, $this);
            $event->setException($exception);

            $this->dispatcher->trigger(self::MESSAGE_FAILURE, $event);

            throw $exception;
        }

        $this->dispatcher->trigger(self::MESSAGE_REQUEST, $event = new EventMessage($message, $this));

        if (null === $codes) {
            return null;
        }

        return $this->readMessageResponse($message, $codes, $timeout);
    }

    /**
     * @inheritDoc
     */
    public function readCommandResponse(Command $command, array $codes, int $timeout = null): ResponseInterface
    {
        try {
            $response = $this->read($codes, $timeout);

            $event = new EventCommand($command, $this);
            $event->setResponse($response);

            $this->dispatcher->trigger(self::COMMAND_SUCCESS, $event);

            return $response;
        } catch (ExceptionInterface $exception) {
            $event = new EventCommand($command, $this);
            $event->setException($exception);

            $this->dispatcher->trigger(self::COMMAND_FAILURE, $event);

            throw $exception;
        }
    }

    /**
     * @inheritDoc
     */
    public function readMessageResponse(string $message, array $codes, int $timeout = null): ResponseInterface
    {
        try {
            $response = $this->read($codes, $timeout);

            $event = new EventMessage($message, $this);
            $event->setResponse($response);

            $this->dispatcher->trigger(self::MESSAGE_SUCCESS, $event);

            return $response;
        } catch (ExceptionInterface $exception) {
            $event = new EventMessage($message, $this);
            $event->setException($exception);

            $this->dispatcher->trigger(self::MESSAGE_FAILURE, $event);

            throw $exception;
        }
    }

    /**
     * @param array    $codes
     * @param int|null $timeout
     *
     * @return ResponseInterface
     *
     * @throws ExceptionInterface
     */
    private function read(array $codes, int $timeout = null): ResponseInterface
    {
        $response = $this->connection->read($timeout);

        if (!empty($codes) && !in_array($code = $response->getCode(), $codes, true)) {
            throw new ResponseException('Invalid response code', $response, $codes);
        }

        return $response;
    }
}
