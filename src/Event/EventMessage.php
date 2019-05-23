<?php

namespace PE\Component\SMTP\Event;

use PE\Component\SMTP\ClientInterface;

final class EventMessage extends EventBase
{
    /**
     * @var string
     */
    private $message;

    /**
     * @inheritDoc
     *
     * @param string $message
     */
    public function __construct(string $message, ClientInterface $client)
    {
        parent::__construct($client);
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
