<?php

namespace PE\Component\SMTP;

final class Command implements CommandInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $params;

    /**
     * @param string   $name
     * @param string[] $params
     */
    public function __construct(string $name, array $params = [])
    {
        $this->name   = $name;
        $this->params = $params;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function addParam(string $param): void
    {
        $this->params[] = $param;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if (!empty($this->params)) {
            return $this->name . ' ' . implode(' ', $this->params);
        }

        return $this->name;
    }
}
