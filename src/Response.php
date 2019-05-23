<?php

namespace PE\Component\SMTP;

final class Response implements ResponseInterface
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string[]
     */
    private $metadata;

    /**
     * @param int         $code
     * @param string|null $message
     * @param string[]    $metadata
     */
    public function __construct(int $code, ?string $message = null, array $metadata = [])
    {
        $this->code     = $code;
        $this->message  = $message;
        $this->metadata = $metadata;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public function getMetadataList(): array
    {
        return $this->metadata;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasMetadataLine(string $name): bool
    {
        foreach ($this->metadata as $line) {
            if (0 === strpos($line, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getMetadataLine(string $name): ?string
    {
        foreach ($this->metadata as $line) {
            if (0 === strpos($line, $name)) {
                return trim(substr($line, strlen($name)));
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $lines = $this->metadata;
        $out   = [$this->code . ($lines ? '-' : ' ') . $this->message];

        while ($line = current($lines)) {
            if (next($lines)) {
                $out[] = $this->code . '-' . $line;
            } else {
                $out[] = $this->code . ' ' . $line;
            }
        }

        return implode("\n", $out);
    }
}
