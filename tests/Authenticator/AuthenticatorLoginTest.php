<?php

namespace PE\Component\SMTP\Tests\Authenticator;

use PE\Component\SMTP\Authenticator\AuthenticatorLogin;
use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticatorLoginTest extends TestCase
{
    /**
     * @var AuthenticatorLogin
     */
    private $authenticator;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->authenticator = new AuthenticatorLogin();
        $this->client        = $this->createMock(ClientInterface::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAuthenticateSuccess(): void
    {
        $this->client
            ->expects(self::once())
            ->method('sendCommand')
            ->with(
                self::callback(static function(Command $c){ return (string) $c === 'AUTH LOGIN'; })
            )->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $this->client
            ->expects(self::exactly(2))
            ->method('sendMessage')
            ->withConsecutive(
                [self::equalTo(base64_encode('username'))],
                [self::equalTo(base64_encode('password'))]
            );

        self::assertTrue($this->authenticator->authenticate($this->client, 'username', 'password'));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAuthenticateFailure(): void
    {
        $exception = new RuntimeException();

        $this->client
            ->expects(self::atLeast(1))
            ->method('sendCommand')
            ->withConsecutive(
                [self::callback(static function(Command $c){ return (string) $c === 'AUTH LOGIN'; })],
                [self::callback(static function(Command $c){ return (string) $c === 'RSET'; })]
            )->willReturnOnConsecutiveCalls(
                self::throwException($exception),
                $this->createMock(ResponseInterface::class)
            );

        $this->expectExceptionObject($exception);

        $this->authenticator->authenticate($this->client, 'username', 'password');
    }
}
