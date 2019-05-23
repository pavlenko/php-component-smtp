<?php

namespace PE\Component\SMTP\LogHandler;

use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\Exception\ResponseException;

final class LogHandlerSTDOUT extends LogHandlerBase
{
    /**
     * @inheritDoc
     */
    public function onCommandCreate(EventCommand $event): void
    {
        echo $this->send((string) $event->getCommand());
    }

    /**
     * @inheritDoc
     */
    public function onCommandSuccess(EventCommand $event): void
    {
        echo $this->read((string) $event->getResponse());
    }

    /**
     * @inheritDoc
     *
     */
    public function onCommandFailure(EventCommand $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseException) {
            echo $this->read((string) $exception->getResponse());
        } else {
            echo $this->read((string) $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function onMessageCreate(EventMessage $event): void
    {
        echo $this->send($event->getMessage());
    }

    /**
     * @inheritDoc
     */
    public function onMessageSuccess(EventMessage $event): void
    {
        if ($response = $event->getResponse()) {
            echo $this->read((string) $response);
        }
    }

    /**
     * @inheritDoc
     */
    public function onMessageFailure(EventMessage $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseException) {
            echo $this->read((string) $exception->getResponse());
        } else {
            echo $this->read((string) $exception);
        }
    }

    /**
     * @inheritDoc
     */
    protected function send(string $message): string
    {
        $date = date_create()->format(DATE_RFC3339_EXTENDED);
        return "{$date} --> {$message}\n";
    }

    /**
     * @inheritDoc
     */
    protected function read(string $message): string
    {
        $date = date_create()->format(DATE_RFC3339_EXTENDED);
        return $date . ' <-- ' . str_replace("\n", "\n{$date} <-- ", $message) . "\n";
    }
}
