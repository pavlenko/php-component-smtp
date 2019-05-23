<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Authenticator\AuthenticatorInterface;
use PE\Component\SMTP\Exception\RuntimeException;
use PE\Component\SMTP\Response;

final class ModuleAuthenticator implements ModuleInterface
{
    /**
     * @var AuthenticatorInterface[]
     */
    private $authenticators = [];

    /**
     * @var string
     */
    private $username = '';

    /**
     * @var string
     */
    private $password = '';

    /**
     * @param AuthenticatorInterface[] $authenticators
     */
    public function __construct(array $authenticators)
    {
        $this->setAuthenticators($authenticators);
    }

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_SUCCESS, [$this, 'onCommandSuccess']]
        ];
    }

    /**
     * @param EventCommand $event
     *
     * @throws ExceptionInterface
     */
    public function onCommandSuccess(EventCommand $event): void
    {
        $command  = $event->getCommand();
        $response = $event->getResponse() ?: new Response(0);

        if ($this->getUsername() && 'EHLO' === $command->getName() && $response->hasMetadataLine('AUTH')) {
            $errors = [];
            $params = (string) $response->getMetadataLine('AUTH');
            $params = explode(' ', $params);

            foreach ($this->getAuthenticators() as $authenticator) {
                if (in_array($authenticator->getAuthenticationMode(), $params, true)) {
                    try {
                        $success = $authenticator->authenticate(
                            $event->getClient(),
                            $this->getUsername(),
                            $this->getPassword()
                        );

                        if ($success) {
                            return;
                        }
                    } catch (\Exception $exception) {
                        $errors[] = $exception;
                    }
                }
            }

            if (!empty($errors)) {
                throw new RuntimeException('Cannot authenticate');
            }
        }
    }

    /**
     * @return AuthenticatorInterface[]
     */
    public function getAuthenticators(): array
    {
        return $this->authenticators;
    }

    /**
     * @param AuthenticatorInterface[] $authenticators
     */
    public function setAuthenticators(array $authenticators): void
    {
        $this->authenticators = [];

        foreach ($authenticators as $authenticator) {
            $this->addAuthenticator($authenticator);
        }
    }

    private function addAuthenticator(AuthenticatorInterface $authenticator): void
    {
        $this->authenticators[get_class($authenticator)] = $authenticator;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
