<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Module\Module8BitMIME;
use PE\Component\SMTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class Module8BitMIMETest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * @var Module8BitMIME
     */
    private $module;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->module = new Module8BitMIME();
    }

    public function testGetClientListeners(): void
    {
        $expected = [
            [ClientInterface::COMMAND_CREATE, [$this->module, 'onCommandCreate']],
            [ClientInterface::COMMAND_SUCCESS, [$this->module, 'onCommandSuccess']],
        ];

        self::assertSame($expected, $this->module->getClientListeners());
    }

    public function testModuleIgnore(): void
    {
        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(0, 'OK'));

        $this->module->onCommandSuccess($event);

        $event = new EventCommand(new Command('MAIL'), $this->client);

        $this->module->onCommandCreate($event);

        self::assertSame('MAIL', (string) $event->getCommand());
    }

    public function testModuleApply(): void
    {
        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(0, 'OK', ['8BITMIME']));

        $this->module->onCommandSuccess($event);

        $event = new EventCommand(new Command('MAIL'), $this->client);

        $this->module->onCommandCreate($event);

        self::assertSame('MAIL BODY=8BITMIME', (string) $event->getCommand());
    }
}
