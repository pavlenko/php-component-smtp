<?php

namespace PE\Component\SMTP\Authenticator;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Exception\ExceptionInterface;

final class AuthenticatorCramMD5 implements AuthenticatorInterface
{
    /**
     * @inheritDoc
     */
    public function getAuthenticationMode(): string
    {
        return 'CRAM-MD5';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ClientInterface $client, string $username, string $password): bool
    {
        try {
            $response  = $client->sendCommand(new Command('AUTH', [$this->getAuthenticationMode()]), [334]);
            $challenge = base64_decode($response->getMessage());

            $digest = $this->createDigest($password, $challenge);

            $client->sendMessage(base64_encode($username . ' ' . $digest), [235]);

            return true;
        } catch (ExceptionInterface $exception) {
            $client->sendCommand(new Command('RSET'), [334]);
            throw $exception;
        }
    }

    /**
     * @param string $password
     * @param string $challenge
     *
     * @return string
     *
     * @internal
     */
    public function createDigest(string $password, string $challenge): string
    {
        if (strlen($password) > 64) {
            $password = pack('H32', md5($password));
        }

        if (strlen($password) < 64) {
            $password = str_pad($password, 64, chr(0));
        }

        $k_ipad = substr($password, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($password, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner  = pack('H32', md5($k_ipad . $challenge));
        $digest = md5($k_opad . $inner);

        return $digest;
    }
}
