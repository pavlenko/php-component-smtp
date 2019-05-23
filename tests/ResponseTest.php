<?php

namespace PE\Component\SMTP\Tests;

use PE\Component\SMTP\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testConstructor(): void
    {
        $response = new Response($code = 220, $message = 'OK', $metadata = ['AUTH PLAIN', 'DSN']);

        self::assertSame($code, $response->getCode());
        self::assertSame($message, $response->getMessage());
        self::assertSame($metadata, $response->getMetadataList());
    }

    public function testMetadata(): void
    {
        $response = new Response(0);

        self::assertFalse($response->hasMetadataLine('AUTH'));
        self::assertNull($response->getMetadataLine('AUTH'));

        $response = new Response(0, '', ['AUTH PLAIN']);

        self::assertTrue($response->hasMetadataLine('AUTH'));
        self::assertSame('PLAIN', $response->getMetadataLine('AUTH'));
    }

    public function testToString1(): void
    {
        $response = new Response(220, 'OK');
        $expected = '220 OK';

        self::assertSame($expected, $response->__toString());
    }

    public function testToString2(): void
    {
        $response = new Response(220, 'OK', ['AUTH PLAIN', 'DSN']);
        $expected = "220-OK\n220-AUTH PLAIN\n220 DSN";

        self::assertSame($expected, $response->__toString());
    }
}
