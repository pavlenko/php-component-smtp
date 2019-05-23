<?php

namespace PE\Component\SMTP\Tests\Authenticator;

use PE\Component\SMTP\Authenticator\AuthenticatorPlain;
use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticatorPlainTest extends TestCase
{
    /**
     * @var AuthenticatorPlain
     */
    private $authenticator;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->authenticator = new AuthenticatorPlain();
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
                self::callback(static function(Command $c){ return (string) $c === 'AUTH PLAIN'; })
            )->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $this->client
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                self::equalTo(base64_encode("\0" . 'username' . "\0" . 'password'))
            )->willReturn(
                $this->createMock(ResponseInterface::class)
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
            ->expects(self::exactly(2))
            ->method('sendCommand')
            ->withConsecutive(
                [self::callback(static function(Command $c){ return (string) $c === 'AUTH PLAIN'; })],
                [self::callback(static function(Command $c){ return (string) $c === 'RSET'; })]
            )->willReturnOnConsecutiveCalls(
                self::throwException($exception),
                $this->createMock(ResponseInterface::class)
            );

        $this->expectExceptionObject($exception);

        $this->authenticator->authenticate($this->client, 'username', 'password');
    }
}
