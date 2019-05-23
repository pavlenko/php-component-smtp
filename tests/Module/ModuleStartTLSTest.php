<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\ConnectionInterface;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Module\ModuleStartTLS;
use PE\Component\SMTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleStartTLSTest extends TestCase
{
    /**
     * @var ModuleStartTLS
     */
    private $module;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->module = new ModuleStartTLS();
        $this->client = $this->createMock(ClientInterface::class);
    }

    public function testGetClientListeners(): void
    {
        $expected = [
            [ClientInterface::COMMAND_SUCCESS, [$this->module, 'onCommandSuccess'], -512]
        ];

        self::assertSame($expected, $this->module->getClientListeners());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandEHLOSuccessSkipIfConnectionHasSecurity(): void
    {
        /* @var $connection ConnectionInterface|MockObject */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('hasSecurity')->willReturn(true);

        $this->client->method('getConnection')->willReturn($connection);

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220));

        self::assertTrue($this->module->onCommandSuccess($event));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandEHLOSuccessSkipIfNotRequiredAndNotSupported(): void
    {
        /* @var $connection ConnectionInterface|MockObject */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('hasSecurity')->willReturn(false);

        $this->client->method('getConnection')->willReturn($connection);

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220));

        self::assertTrue($this->module->onCommandSuccess($event));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandEHLOSuccessTriggerCommandIfSupported(): void
    {
        /* @var $connection ConnectionInterface|MockObject */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('hasSecurity')->willReturn(false);

        $this->client->method('getConnection')->willReturn($connection);

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220, '', ['STARTTLS']));

        self::assertFalse($this->module->onCommandSuccess($event));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandEHLOSuccessThrowExceptionIfRequiredAndNotSupported(): void
    {
        $module = new ModuleStartTLS(true);

        /* @var $connection ConnectionInterface|MockObject */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('hasSecurity')->willReturn(false);

        $this->client->method('getConnection')->willReturn($connection);

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220));

        $this->expectException(RuntimeException::class);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandEHLOSuccessIfResponseNotMatch(): void
    {
        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220));

        self::assertTrue($this->module->onCommandSuccess($event));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSTARTTLSSuccess(): void
    {
        /* @var $connection ConnectionInterface|MockObject */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())->method('setEncryption');

        $this->client->expects(self::once())->method('getConnection')->willReturn($connection);
        $this->client->expects(self::once())->method('HELO');

        $event = new EventCommand(new Command('STARTTLS'), $this->client);

        self::assertTrue($this->module->onCommandSuccess($event));
    }
}
