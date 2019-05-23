<?php

namespace PE\Component\SMTP;

interface ResponseInterface
{
    /**
     * @return int
     */
    public function getCode(): int;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @return string[]
     */
    public function getMetadataList(): array;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasMetadataLine(string $name): bool;

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getMetadataLine(string $name): ?string;
}
