<?php

namespace PE\Component\SMTP\Event;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Exception\ExceptionInterface;
use PE\Component\SMTP\ResponseInterface;

abstract class EventBase
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var ExceptionInterface|null
     */
    private $exception;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface|null $response
     *
     * @return static
     */
    public function setResponse(?ResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return ExceptionInterface|null
     */
    public function getException(): ?ExceptionInterface
    {
        return $this->exception;
    }

    /**
     * @param ExceptionInterface|null $exception
     *
     * @return static
     */
    public function setException(?ExceptionInterface $exception): self
    {
        $this->exception = $exception;
        return $this;
    }
}
