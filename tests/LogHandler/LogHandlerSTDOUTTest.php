<?php

namespace PE\Component\SMTP\Tests\LogHandler;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\Exception\ResponseException;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\LogHandler\LogHandlerSTDOUT;
use PE\Component\SMTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogHandlerSTDOUTTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * @var LogHandlerSTDOUT
     */
    private $handler;

    protected function setUp(): void
    {
        $this->client  = $this->createMock(ClientInterface::class);
        $this->handler = new LogHandlerSTDOUT();
    }

    public function testOnCommandCreate(): void
    {
        $this->expectOutputRegex('#EHLO#');

        $event = new EventCommand(new Command('EHLO'), $this->client);

        $this->handler->onCommandCreate($event);
    }

    public function testOnCommandSuccess(): void
    {
        $this->expectOutputRegex('#220#');

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setResponse(new Response(220));

        $this->handler->onCommandSuccess($event);
    }

    public function testOnCommandFailureNoResponse(): void
    {
        $this->expectOutputRegex('#' . preg_quote('<-- ERR') . '#');

        /* @var $exception RuntimeException|MockObject */
        $exception = $this->createMock(RuntimeException::class);
        $exception->expects(self::once())->method('__toString')->willReturn('ERR');

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setException($exception);

        $this->handler->onCommandFailure($event);
    }

    public function testOnCommandFailureHasResponse(): void
    {
        $this->expectOutputRegex('#' . preg_quote('<-- 0') . '#');

        /* @var $exception ResponseException|MockObject */
        $exception = $this->createMock(ResponseException::class);
        $exception->expects(self::once())->method('getResponse')->willReturn(new Response(0));

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setException($exception);

        $this->handler->onCommandFailure($event);
    }

    public function testOnMessageCreate(): void
    {
        $this->expectOutputRegex('#message#');

        $event = new EventMessage('message', $this->client);

        $this->handler->onMessageCreate($event);
    }

    public function testOnMessageSuccess(): void
    {
        $this->expectOutputRegex('#220#');

        $event = new EventMessage('message', $this->client);
        $event->setResponse(new Response(220));

        $this->handler->onMessageSuccess($event);
    }

    public function testOnMessageFailureNoResponse(): void
    {
        $this->expectOutputRegex('#' . preg_quote('<-- ERR') . '#');

        /* @var $exception RuntimeException|MockObject */
        $exception = $this->createMock(RuntimeException::class);
        $exception->expects(self::once())->method('__toString')->willReturn('ERR');

        $event = new EventMessage('message', $this->client);
        $event->setException($exception);

        $this->handler->onMessageFailure($event);
    }

    public function testOnMessageFailureHasResponse(): void
    {
        $this->expectOutputRegex('#' . preg_quote('<-- 0') . '#');

        /* @var $exception ResponseException|MockObject */
        $exception = $this->createMock(ResponseException::class);
        $exception->expects(self::once())->method('getResponse')->willReturn(new Response(0));

        $event = new EventMessage('message', $this->client);
        $event->setException($exception);

        $this->handler->onMessageFailure($event);
    }
}
