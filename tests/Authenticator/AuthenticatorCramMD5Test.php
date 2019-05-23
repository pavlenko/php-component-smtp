<?php

namespace PE\Component\SMTP\Tests\Authenticator;

use PE\Component\SMTP\Authenticator\AuthenticatorCramMD5;
use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Response;
use PE\Component\SMTP\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticatorCramMD5Test extends TestCase
{
    /**
     * @var AuthenticatorCramMD5
     */
    private $authenticator;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->authenticator = new AuthenticatorCramMD5();
        $this->client        = $this->createMock(ClientInterface::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAuthenticateSuccess(): void
    {
        $password = hash('sha512', 'password');

        $this->client
            ->expects(self::once())
            ->method('sendCommand')
            ->with(
                self::callback(static function(Command $c){ return (string) $c === 'AUTH CRAM-MD5'; })
            )->willReturn(
                new Response(334, base64_encode('challenge'))
            );

        $message = base64_encode('username' . ' ' . $this->authenticator->createDigest($password, 'challenge'));

        $this->client
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $message
            );

        self::assertTrue($this->authenticator->authenticate($this->client, 'username', $password));
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
                [self::callback(static function(Command $c){ return (string) $c === 'AUTH CRAM-MD5'; })],
                [self::callback(static function(Command $c){ return (string) $c === 'RSET'; })]
            )->willReturnOnConsecutiveCalls(
                self::throwException($exception),
                $this->createMock(ResponseInterface::class)
            );

        $this->expectExceptionObject($exception);

        $this->authenticator->authenticate($this->client, 'username', 'password');
    }
}
