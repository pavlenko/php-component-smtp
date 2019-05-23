<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;

final class ModulePipelining implements ModuleInterface
{
    /**
     * @var bool
     */
    private $supported = false;

    /**
     * @var array
     */
    private $pipelined = [];

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_REQUEST, [$this, 'onCommandRequest']],
            [ClientInterface::COMMAND_SUCCESS, [$this, 'onCommandSuccess']],
        ];
    }

    /**
     * @internal
     *
     * @param EventCommand $event
     *
     * @throws ExceptionInterface
     */
    public function onCommandRequest(EventCommand $event): void
    {
        $client  = $event->getClient();
        $command = $event->getCommand();

        if ($this->supported) {
            if (in_array($command->getName(), ['MAIL', 'RCPT', 'RSET'])) {
                $this->pipelined[] = [$command, $event->getExpectedCodes(), $event->getExpectedTimeout()];
                $event->setExpectedResponse(false);
                return;
            }

            while (count($this->pipelined)) {
                list($command, $codes, $timeout) = array_shift($this->pipelined);

                $client->readCommandResponse($command, $codes, $timeout);
            }
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

        if ($command->getName() === 'EHLO') {
            $this->supported = $response->hasMetadataLine('PIPELINING');
        }
    }
}
