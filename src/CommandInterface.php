<?php

namespace PE\Component\SMTP;

interface CommandInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $param
     */
    public function addParam(string $param): void;

    /**
     * @inheritDoc
     */
    public function __toString(): string;
}
