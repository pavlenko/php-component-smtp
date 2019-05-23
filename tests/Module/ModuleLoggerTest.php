<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\LogHandler\LogHandlerInterface;
use PE\Component\SMTP\Module\ModuleLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleLoggerTest extends TestCase
{
    /**
     * @var LogHandlerInterface|MockObject
     */
    private $logHandler;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->logHandler = $this->createMock(LogHandlerInterface::class);
        $this->client     = $this->createMock(ClientInterface::class);
    }

    public function testGetClientListeners(): void
    {
        $module = new ModuleLogger($this->logHandler);

        $expected = [
            [ClientInterface::COMMAND_CREATE, [$this->logHandler, 'onCommandCreate'], 1024],
            [ClientInterface::COMMAND_SUCCESS, [$this->logHandler, 'onCommandSuccess'], -1024],
            [ClientInterface::COMMAND_FAILURE, [$this->logHandler, 'onCommandFailure'], -1024],
            [ClientInterface::MESSAGE_CREATE, [$this->logHandler, 'onMessageCreate'], 1024],
            [ClientInterface::MESSAGE_SUCCESS, [$this->logHandler, 'onMessageSuccess'], -1024],
            [ClientInterface::MESSAGE_FAILURE, [$this->logHandler, 'onMessageFailure'], -1024],
        ];

        self::assertSame($expected, $module->getClientListeners());
    }
}
