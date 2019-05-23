<?php

namespace PE\Component\SMTP\Event;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\CommandInterface;

final class EventCommand extends EventBase
{
    /**
     * @var CommandInterface
     */
    private $command;

    /**
     * @var bool
     */
    private $expectedResponse = true;

    /**
     * @var int[]
     */
    private $expectedCodes = [];

    /**
     * @var int|null
     */
    private $expectedTimeout = null;

    /**
     * @inheritDoc
     *
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command, ClientInterface $client)
    {
        parent::__construct($client);
        $this->command = $command;
    }

    /**
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    /**
     * @return bool
     */
    public function isExpectedResponse(): bool
    {
        return $this->expectedResponse;
    }

    /**
     * @param bool $expectedResponse
     */
    public function setExpectedResponse(bool $expectedResponse): void
    {
        $this->expectedResponse = $expectedResponse;
    }

    /**
     * @return int[]
     */
    public function getExpectedCodes(): array
    {
        return $this->expectedCodes;
    }

    /**
     * @param int[] $expectedCodes
     */
    public function setExpectedCodes(array $expectedCodes): void
    {
        $this->expectedCodes = $expectedCodes;
    }

    /**
     * @return int|null
     */
    public function getExpectedTimeout(): ?int
    {
        return $this->expectedTimeout;
    }

    /**
     * @param int|null $expectedTimeout
     */
    public function setExpectedTimeout(?int $expectedTimeout): void
    {
        $this->expectedTimeout = $expectedTimeout;
    }
}
