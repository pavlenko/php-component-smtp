<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\Authenticator\AuthenticatorInterface;
use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\CommandInterface;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Module\ModuleAuthenticator;
use PE\Component\SMTP\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleAuthenticatorTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
    }

    public function testGetClientListeners(): void
    {
        $module = new ModuleAuthenticator([]);

        $expected = [
            [ClientInterface::COMMAND_SUCCESS, [$module, 'onCommandSuccess']]
        ];

        self::assertSame($expected, $module->getClientListeners());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessSkipIfNoUsername(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::never())->method('getName');

        $module = new ModuleAuthenticator([]);
        $event  = new EventCommand($command, $this->client);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessSkipIfNoMatchEvent(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('MAIL');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::never())->method('hasMetadataLine');

        $module = new ModuleAuthenticator([]);
        $module->setUsername('username');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessSkipIfNoMatchResponse(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('EHLO');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('hasMetadataLine')->willReturn(false);
        $response->expects(self::never())->method('getMetadataLine');

        $module = new ModuleAuthenticator([]);
        $module->setUsername('username');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessNoAuthenticators(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('EHLO');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('hasMetadataLine')->willReturn(true);
        $response->expects(self::once())->method('getMetadataLine')->willReturn('PLAIN LOGIN');

        $module = new ModuleAuthenticator([]);
        $module->setUsername('username');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessAuthenticatorFailure(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('EHLO');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('hasMetadataLine')->willReturn(true);
        $response->expects(self::once())->method('getMetadataLine')->willReturn('PLAIN LOGIN');

        /* @var $authenticator AuthenticatorInterface|MockObject */
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $authenticator->method('getAuthenticationMode')->willReturn('PLAIN');
        $authenticator->method('authenticate')->willThrowException(new \Exception());

        $module = new ModuleAuthenticator([$authenticator]);
        $module->setUsername('username');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $this->expectException(RuntimeException::class);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessAuthenticatorSuccess(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('EHLO');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('hasMetadataLine')->willReturn(true);
        $response->expects(self::once())->method('getMetadataLine')->willReturn('PLAIN LOGIN');

        /* @var $authenticator AuthenticatorInterface|MockObject */
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $authenticator->method('getAuthenticationMode')->willReturn('PLAIN');
        $authenticator->method('authenticate')->with($this->client, 'username', 'password')->willReturn(true);

        $module = new ModuleAuthenticator([$authenticator]);
        $module->setUsername('username');
        $module->setPassword('password');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $module->onCommandSuccess($event);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testOnCommandSuccessAuthenticatorSuccessIfPreviousFailure(): void
    {
        /* @var $command CommandInterface|MockObject */
        $command = $this->createMock(CommandInterface::class);
        $command->expects(self::once())->method('getName')->willReturn('EHLO');

        /* @var $response ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('hasMetadataLine')->willReturn(true);
        $response->expects(self::once())->method('getMetadataLine')->willReturn('PLAIN LOGIN');

        /* @var $authenticator1 AuthenticatorInterface|MockObject */
        $authenticator1 = $this->createMock(AuthenticatorInterface::class);
        $authenticator1->method('getAuthenticationMode')->willReturn('PLAIN');
        $authenticator1->method('authenticate')->willThrowException(new \Exception());

        /* @var $authenticator2 AuthenticatorInterface|MockObject */
        $authenticator2 = $this->createMock(AuthenticatorInterface::class);
        $authenticator2->method('getAuthenticationMode')->willReturn('LOGIN');
        $authenticator2->method('authenticate')->willReturn(true);

        $module = new ModuleAuthenticator([$authenticator1, $authenticator2]);
        $module->setUsername('username');

        $event = new EventCommand($command, $this->client);
        $event->setResponse($response);

        $module->onCommandSuccess($event);
    }
}
