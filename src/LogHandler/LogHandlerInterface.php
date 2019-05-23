<?php

namespace PE\Component\SMTP\LogHandler;

use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;

interface LogHandlerInterface
{
    /**
     * @param EventCommand $event
     */
    public function onCommandCreate(EventCommand $event): void;

    /**
     * @param EventCommand $event
     */
    public function onCommandSuccess(EventCommand $event): void;

    /**
     * @param EventCommand $event
     */
    public function onCommandFailure(EventCommand $event): void;

    /**
     * @param EventMessage $event
     */
    public function onMessageCreate(EventMessage $event): void;

    /**
     * @param EventMessage $event
     */
    public function onMessageSuccess(EventMessage $event): void;

    /**
     * @param EventMessage $event
     */
    public function onMessageFailure(EventMessage $event): void;
}
