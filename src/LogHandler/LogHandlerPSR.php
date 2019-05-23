<?php

namespace PE\Component\SMTP\LogHandler;

use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\Exception\ResponseException;
use Psr\Log\LoggerInterface;

final class LogHandlerPSR extends LogHandlerBase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function onCommandCreate(EventCommand $event): void
    {
        $this->logger->info($this->send((string) $event->getCommand()));
    }

    /**
     * @inheritDoc
     */
    public function onCommandSuccess(EventCommand $event): void
    {
        $this->logger->info($this->read((string) $event->getResponse()));
    }

    /**
     * @inheritDoc
     */
    public function onCommandFailure(EventCommand $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseException) {
            $this->logger->error($this->read((string) $exception->getResponse()));
        } else {
            $this->logger->error($this->read((string) $exception));
        }
    }

    /**
     * @inheritDoc
     */
    public function onMessageCreate(EventMessage $event): void
    {
        $this->logger->info($this->send($event->getMessage()));
    }

    /**
     * @inheritDoc
     */
    public function onMessageSuccess(EventMessage $event): void
    {
        if ($response = $event->getResponse()) {
            $this->logger->info($this->read((string) $response));
        }
    }

    /**
     * @inheritDoc
     */
    public function onMessageFailure(EventMessage $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseException) {
            $this->logger->error($this->read((string) $exception->getResponse()));
        } else {
            $this->logger->error($this->read((string) $exception));
        }
    }
}
