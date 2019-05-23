<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\Exception\RuntimeException;

final class ModuleStartTLS implements ModuleInterface
{
    /**
     * @var bool
     */
    private $required;

    /**
     * @param bool $required
     */
    public function __construct(bool $required = false)
    {
        $this->required = $required;
    }

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_SUCCESS, [$this, 'onCommandSuccess'], -512]
        ];
    }

    /**
     * @param EventCommand $event
     *
     * @return bool
     *
     * @throws ExceptionInterface
     */
    public function onCommandSuccess(EventCommand $event): bool
    {
        $client   = $event->getClient();
        $command  = $event->getCommand();
        $response = $event->getResponse();

        switch ($command->getName()) {
            case 'EHLO':
                if ($client->getConnection()->hasSecurity()) {
                    break;
                }

                if ($response->hasMetadataLine('STARTTLS')) {
                    $client->sendCommand(new Command('STARTTLS'), [220], 180);
                    return false;// Return false for stop pending event handlers
                }

                if ($this->required) {
                    throw new RuntimeException('TLS security is required but not supported by remote server');
                }

                break;
            case 'STARTTLS':
                // Allow the best TLS version(s) we can
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

                // PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
                // so add them back in manually if we can
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
                }

                $client->getConnection()->setEncryption($cryptoMethod);
                $client->HELO();

                break;
        }

        return true;
    }
}
