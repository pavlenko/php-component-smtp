<?php

namespace PE\Component\SMTP\Tests\LogHandler;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Event\EventMessage;
use PE\Component\SMTP\Exception\ResponseException;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\LogHandler\LogHandlerPSR;
use PE\Component\SMTP\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogHandlerPSRTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var LogHandlerPSR
     */
    private $handler;

    protected function setUp(): void
    {
        $this->client  = $this->createMock(ClientInterface::class);
        $this->logger  = $this->createMock(LoggerInterface::class);
        $this->handler = new LogHandlerPSR($this->logger);
    }

    public function testOnCommandCreate(): void
    {
        $this->logger->expects(self::once())->method('info');

        $event = new EventCommand(new Command('EHLO'), $this->client);

        $this->handler->onCommandCreate($event);
    }

    public function testOnCommandSuccess(): void
    {
        $this->logger->expects(self::once())->method('info');

        $event = new EventCommand(new Command('EHLO'), $this->client);

        $this->handler->onCommandSuccess($event);
    }

    public function testOnCommandFailureNoResponse(): void
    {
        $this->logger->expects(self::once())->method('error');

        /* @var $exception RuntimeException|MockObject */
        $exception = $this->createMock(RuntimeException::class);
        $exception->expects(self::once())->method('__toString')->willReturn('');

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setException($exception);

        $this->handler->onCommandFailure($event);
    }

    public function testOnCommandFailure(): void
    {
        $this->logger->expects(self::once())->method('error');

        /* @var $exception ResponseException|MockObject */
        $exception = $this->createMock(ResponseException::class);
        $exception->expects(self::once())->method('getResponse')->willReturn(new Response(0));

        $event = new EventCommand(new Command('EHLO'), $this->client);
        $event->setException($exception);

        $this->handler->onCommandFailure($event);
    }

    public function testOnMessageCreate(): void
    {
        $this->logger->expects(self::once())->method('info');

        $event = new EventMessage('message', $this->client);

        $this->handler->onMessageCreate($event);
    }

    public function testOnMessageSuccessNoResponse(): void
    {
        $this->logger->expects(self::never())->method('info');

        $event = new EventMessage('message', $this->client);

        $this->handler->onMessageSuccess($event);
    }

    public function testOnMessageSuccessHasResponse(): void
    {
        $this->logger->expects(self::once())->method('info');

        $event = new EventMessage('message', $this->client);
        $event->setResponse(new Response(0));

        $this->handler->onMessageSuccess($event);
    }

    public function testOnMessageFailureNoResponse(): void
    {
        $this->logger->expects(self::once())->method('error');

        /* @var $exception RuntimeException|MockObject */
        $exception = $this->createMock(RuntimeException::class);
        $exception->expects(self::once())->method('__toString')->willReturn('');

        $event = new EventMessage('message', $this->client);
        $event->setException($exception);

        $this->handler->onMessageFailure($event);
    }

    public function testOnMessageFailureHasResponse(): void
    {
        $this->logger->expects(self::once())->method('error');

        /* @var $exception ResponseException|MockObject */
        $exception = $this->createMock(ResponseException::class);
        $exception->expects(self::once())->method('getResponse')->willReturn(new Response(0));

        $event = new EventMessage('message', $this->client);
        $event->setException($exception);

        $this->handler->onMessageFailure($event);
    }
}
