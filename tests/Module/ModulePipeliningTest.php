<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Module\ModulePipelining;
use PE\Component\SMTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModulePipeliningTest extends TestCase
{
    /**
     * @var ModulePipelining
     */
    private $module;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp()
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->module = new ModulePipelining();
    }


    public function testGetClientListeners()
    {
        $expected = [
            [ClientInterface::COMMAND_REQUEST, [$this->module, 'onCommandRequest']],
            [ClientInterface::COMMAND_SUCCESS, [$this->module, 'onCommandSuccess']],
        ];

        self::assertSame($expected, $this->module->getClientListeners());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testPipeliningNotSupported()
    {
        $event = new EventCommand(new Command('MAIL'), $this->client);

        $this->module->onCommandRequest($event);

        self::assertTrue($event->isExpectedResponse());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testPipeliningSupported()
    {
        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(0, 'OK', ['PIPELINING']));

        $this->module->onCommandSuccess($event);

        $event = new EventCommand(new Command('MAIL'), $this->client);

        $this->module->onCommandRequest($event);

        self::assertFalse($event->isExpectedResponse());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testPipelinedLogic()
    {
        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(0, 'OK', ['PIPELINING']));

        $this->module->onCommandSuccess($event);

        $this->module->onCommandRequest($eventCommand1 = new EventCommand(new Command('MAIL'), $this->client));
        $this->module->onCommandRequest($eventCommand2 = new EventCommand(new Command('RCPT'), $this->client));

        $this->client
            ->expects(self::exactly(2))
            ->method('readCommandResponse')
            ->withConsecutive([$eventCommand1->getCommand()], [$eventCommand2->getCommand()]);

        $this->module->onCommandRequest(new EventCommand(new Command('DATA'), $this->client));
    }
}
