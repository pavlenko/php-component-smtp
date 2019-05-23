<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Event\EventCommand;

final class Module8BitMIME implements ModuleInterface
{
    /**
     * @var bool
     */
    private $supported = false;

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_CREATE, [$this, 'onCommandCreate']],
            [ClientInterface::COMMAND_SUCCESS, [$this, 'onCommandSuccess']],
        ];
    }

    /**
     * @internal
     *
     * @param EventCommand $event
     */
    public function onCommandCreate(EventCommand $event): void
    {
        $command = $event->getCommand();

        if ($this->supported && 'MAIL' === $command->getName()) {
            $command->addParam('BODY=8BITMIME');
        }
    }

    /**
     * @internal
     *
     * @param EventCommand $event
     */
    public function onCommandSuccess(EventCommand $event): void
    {
        $command  = $event->getCommand();
        $response = $event->getResponse();

        if ('EHLO' === $command->getName()) {
            $this->supported = $response && $response->hasMetadataLine('8BITMIME');
        }
    }
}
