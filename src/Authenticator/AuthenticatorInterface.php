<?php

namespace PE\Component\SMTP\Authenticator;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Exception\ExceptionInterface;

interface AuthenticatorInterface
{
    /**
     * @return string
     */
    public function getAuthenticationMode(): string;

    /**
     * @param ClientInterface $client
     * @param string          $username
     * @param string          $password
     *
     * @return bool
     *
     * @throws ExceptionInterface
     */
    public function authenticate(ClientInterface $client, string $username, string $password): bool;
}
