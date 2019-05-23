<?php

namespace PE\Component\SMTP\Authenticator;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Exception\ExceptionInterface;

final class AuthenticatorLogin implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function getAuthenticationMode(): string
    {
        return 'LOGIN';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ClientInterface $client, string $username, string $password): bool
    {
        try {
            $client->sendCommand(new Command('AUTH', [$this->getAuthenticationMode()]), [334]);
            $client->sendMessage(base64_encode($username), [334]);
            $client->sendMessage(base64_encode($password), [235]);

            return true;
        } catch (ExceptionInterface $exception) {
            $client->sendCommand(new Command('RSET'), [250]);
            throw $exception;
        }
    }
}
