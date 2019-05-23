<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\LogHandler\LogHandlerInterface;

final class ModuleLogger implements ModuleInterface
{
    /**
     * @var LogHandlerInterface
     */
    private $logHandler;

    /**
     * @param LogHandlerInterface $logHandler
     */
    public function __construct(LogHandlerInterface $logHandler)
    {
        $this->logHandler = $logHandler;
    }

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_CREATE, [$this->logHandler, 'onCommandCreate'], 1024],
            [ClientInterface::COMMAND_SUCCESS, [$this->logHandler, 'onCommandSuccess'], -1024],
            [ClientInterface::COMMAND_FAILURE, [$this->logHandler, 'onCommandFailure'], -1024],
            [ClientInterface::MESSAGE_CREATE, [$this->logHandler, 'onMessageCreate'], 1024],
            [ClientInterface::MESSAGE_SUCCESS, [$this->logHandler, 'onMessageSuccess'], -1024],
            [ClientInterface::MESSAGE_FAILURE, [$this->logHandler, 'onMessageFailure'], -1024],
        ];
    }
}
