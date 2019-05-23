<?php

namespace PE\Component\SMTP\Tests;

use PE\Component\SMTP\Client;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\ConnectionInterface;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\EventEmitterInterface;
use PE\Component\SMTP\Exception\ConnectionException;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\ResponseException;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Module\ModuleInterface;
use PE\Component\SMTP\Response;
use PE\Component\SMTP\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var ConnectionInterface|MockObject
     */
    private $connection;

    /**
     * @var EventEmitterInterface|MockObject
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->dispatcher = $this->createMock(EventEmitterInterface::class);
    }

    public function testGetSetName(): void
    {
        $client = new Client($this->connection);

        self::assertSame('localhost', $client->getName());

        $client->setName('example.com');

        self::assertSame('example.com', $client->getName());
    }

    public function testGetSetConnection(): void
    {
        /**
         * @var $connection1 ConnectionInterface|MockObject
         * @var $connection2 ConnectionInterface|MockObject
         */
        $connection1 = $this->createMock(ConnectionInterface::class);
        $connection2 = $this->createMock(ConnectionInterface::class);

        $client = new Client($connection1);

        self::assertSame($connection1, $client->getConnection());

        $client->setConnection($connection2);

        self::assertSame($connection2, $client->getConnection());
    }

    public function testAttachModule(): void
    {
        $listener = function(){};

        /* @var $module ModuleInterface|MockObject */
        $module = $this->createMock(ModuleInterface::class);
        $module->expects(self::once())->method('getClientListeners')->willReturn([['FOO', $listener]]);

        $this->dispatcher->expects(self::once())->method('attachListener')->with('FOO', $listener);

        $client = new Client($this->connection, $this->dispatcher);
        $client->attachModule($module);
        $client->attachModule($module);
    }

    public function testDetachModule(): void
    {
        $listener = function(){};

        /* @var $module ModuleInterface|MockObject */
        $module = $this->createMock(ModuleInterface::class);
        $module->expects(self::exactly(2))->method('getClientListeners')->willReturn([['FOO', $listener]]);

        $this->dispatcher->expects(self::once())->method('detachListener')->with('FOO', $listener);

        $client = new Client($this->connection, $this->dispatcher);
        $client->attachModule($module);
        $client->detachModule($module);
        $client->detachModule($module);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testConnect(): void
    {
        $this->connection->expects(self::once())->method('open');
        $this->connection->expects(self::once())->method('read')->willReturn(new Response(220));

        $client = new Client($this->connection, $this->dispatcher);
        $client->connect();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDisconnect()
    {
        $this->connection->expects(self::atLeastOnce())->method('exit');

        $client = new Client($this->connection, $this->dispatcher);
        $client->disconnect();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHELOFallback(): void
    {
        $this->connection
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                ['EHLO localhost'],
                ['HELO localhost']
            )
            ->willReturn(0);

        $this->connection
            ->expects(self::exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Response(500),
                new Response(250)
            );

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHELOSuccess(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('send')
            ->with('EHLO localhost')
            ->willReturn(0);

        $this->connection
            ->expects(self::once())
            ->method('read')
            ->willReturn(new Response(250));

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testMAILFailureIfNoSess(): void
    {
        $this->expectException(RuntimeException::class);

        (new Client($this->connection))->MAIL('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testMAILSuccess(): void
    {
        $this->connection
            ->expects(self::exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Response(250),
                new Response(250)
            );

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
        $client->MAIL('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRCPTFailureIfNoMAIL(): void
    {
        $this->expectException(RuntimeException::class);

        (new Client($this->connection))->RCPT('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRCPTSuccess(): void
    {
        $this->connection
            ->expects(self::exactly(3))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Response(250),
                new Response(250),
                new Response(250)
            );

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
        $client->MAIL('FOO');
        $client->RCPT('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDATAFailureIfNoRCPT(): void
    {
        $this->expectException(RuntimeException::class);

        (new Client($this->connection))->DATA('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDATASuccess(): void
    {
        $this->connection
            ->expects(self::exactly(6))
            ->method('send')
            ->withConsecutive(
                ['EHLO localhost'],
                ['MAIL FROM:<FROM>'],
                ['RCPT TO:<TO>'],
                ['DATA'],
                ['..FOO BAR'],
                ['.']
            )
            ->willReturn(0);

        $this->connection
            ->expects(self::exactly(5))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Response(250),
                new Response(250),
                new Response(250),
                new Response(354),
                new Response(250)
            );

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
        $client->MAIL('FROM');
        $client->RCPT('TO');
        $client->DATA('.FOO BAR');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testRSET(): void
    {
        $this->connection->method('read')->willReturn(new Response(250));

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();
        $client->MAIL('FOO');
        $client->RCPT('FOO');

        $this->connection->expects(self::once())->method('send')->with('RSET')->willReturn(0);

        $client->RSET();

        $this->expectException(RuntimeException::class);

        $client->RCPT('FOO');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testVRFY(): void
    {
        $this->connection->method('read')->willReturn(new Response(250));

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();

        $this->connection->expects(self::once())->method('send')->with('VRFY USER')->willReturn(0);

        $client->VRFY('USER');
    }

    /**
     * @throws ExceptionInterface
     */
    public function testNOOP(): void
    {
        $this->connection->method('read')->willReturn(new Response(250));

        $client = new Client($this->connection);
        $client->setEnabledAutoQuit(false);
        $client->HELO();

        $this->connection->expects(self::once())->method('send')->with('NOOP')->willReturn(0);

        $client->NOOP();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testQUITSkipIfNoSession()
    {
        $this->dispatcher->expects(self::never())->method('trigger');

        $client = new Client($this->connection, $this->dispatcher);
        $client->QUIT();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testQUITSkipIfNoAutoQuit()
    {
        $this->connection->method('read')->willReturn(new Response(250));

        $client = new Client($this->connection, $this->dispatcher);
        $client->setEnabledAutoQuit(false);
        $client->HELO();

        $this->dispatcher->expects(self::never())->method('trigger');

        $client->QUIT();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testQUITSuccess()
    {
        $this->connection->method('read')->willReturnOnConsecutiveCalls(new Response(250), new Response(221));

        $client = new Client($this->connection, $this->dispatcher);
        $client->HELO();

        $this->dispatcher->expects(self::atLeastOnce())->method('trigger');

        $client->QUIT();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendCommandFailureOnSend(): void
    {
        $exception = new ConnectionException();

        $this->connection->expects(self::once())->method('send')->willThrowException($exception);

        $this->dispatcher
            ->expects(self::exactly(2))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::COMMAND_CREATE), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_FAILURE), self::isInstanceOf(EventCommand::class)]
            );

        $this->expectExceptionObject($exception);

        (new Client($this->connection, $this->dispatcher))->sendCommand(new Command('EHLO'), []);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendCommandFailureOnRead(): void
    {
        $exception = new ConnectionException();

        $this->connection->expects(self::once())->method('read')->willThrowException($exception);

        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::COMMAND_CREATE), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_REQUEST), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_FAILURE), self::isInstanceOf(EventCommand::class)]
            );

        $this->expectExceptionObject($exception);

        (new Client($this->connection, $this->dispatcher))->sendCommand(new Command('EHLO'), []);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendCommandFailureOnCode(): void
    {
        $this->connection->expects(self::once())->method('read')->willReturn(new Response(1));

        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::COMMAND_CREATE), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_REQUEST), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_FAILURE), self::isInstanceOf(EventCommand::class)]
            );

        $this->expectException(ResponseException::class);

        (new Client($this->connection, $this->dispatcher))->sendCommand(new Command('EHLO'), [0]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendCommandSuccessWithResponse(): void
    {
        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::COMMAND_CREATE), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_REQUEST), self::isInstanceOf(EventCommand::class)],
                [self::equalTo(Client::COMMAND_SUCCESS), self::isInstanceOf(EventCommand::class)]
            );

        $response = (new Client($this->connection, $this->dispatcher))->sendCommand(new Command('EHLO'), []);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendCommandSuccessSkipResponse(): void
    {
        $this->dispatcher->expects(self::exactly(2))->method('trigger')->willReturnOnConsecutiveCalls(
            1,
            self::returnCallback(static function ($name, EventCommand $event) {
                $event->setExpectedResponse(false);
                return (int) (Client::COMMAND_REQUEST === $name);
            })
        );

        $response = (new Client($this->connection, $this->dispatcher))->sendCommand(new Command('EHLO'), []);

        self::assertNull($response);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendMessageFailureOnSend(): void
    {
        $exception = new ConnectionException();

        $this->connection->expects(self::once())->method('send')->willThrowException($exception);

        $this->dispatcher
            ->expects(self::exactly(2))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::MESSAGE_CREATE), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_FAILURE), self::isInstanceOf(EventMessage::class)]
            );

        $this->expectExceptionObject($exception);

        (new Client($this->connection, $this->dispatcher))->sendMessage('AAA', []);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendMessageFailureOnRead(): void
    {
        $exception = new ConnectionException();

        $this->connection->expects(self::once())->method('read')->willThrowException($exception);

        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::MESSAGE_CREATE), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_REQUEST), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_FAILURE), self::isInstanceOf(EventMessage::class)]
            );

        $this->expectExceptionObject($exception);

        (new Client($this->connection, $this->dispatcher))->sendMessage('AAA', []);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendMessageFailureOnCode(): void
    {
        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::MESSAGE_CREATE), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_REQUEST), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_FAILURE), self::isInstanceOf(EventMessage::class)]
            );

        $this->expectException(ResponseException::class);

        (new Client($this->connection, $this->dispatcher))->sendMessage('AAA', [1]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendMessageSuccessWithResponse(): void
    {
        $this->dispatcher
            ->expects(self::exactly(3))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::MESSAGE_CREATE), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_REQUEST), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_SUCCESS), self::isInstanceOf(EventMessage::class)]
            );

        $response = (new Client($this->connection, $this->dispatcher))->sendMessage('AAA', []);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testSendMessageSuccessSkipResponse(): void
    {
        $this->dispatcher
            ->expects(self::exactly(2))
            ->method('trigger')
            ->withConsecutive(
                [self::equalTo(Client::MESSAGE_CREATE), self::isInstanceOf(EventMessage::class)],
                [self::equalTo(Client::MESSAGE_REQUEST), self::isInstanceOf(EventMessage::class)]
            );

        $response = (new Client($this->connection, $this->dispatcher))->sendMessage('AAA');

        self::assertNull($response);
    }
}
