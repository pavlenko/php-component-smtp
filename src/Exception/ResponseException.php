<?php

namespace PE\Component\SMTP\Exception;

use PE\Component\SMTP\ResponseInterface;

class ResponseException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var array
     */
    private $expected;

    /**
     * @param string            $message
     * @param ResponseInterface $response
     * @param string[]          $expected
     */
    public function __construct(string $message, ResponseInterface $response, array $expected = [])
    {
        parent::__construct($message);

        $this->response = $response;
        $this->expected = $expected;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return string[]
     */
    public function getExpected(): array
    {
        return $this->expected;
    }
}
