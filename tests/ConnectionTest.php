<?php

namespace PE\Component\SMTP\Tests;

use PE\Component\SMTP\Connection;
use PE\Component\SMTP\Exception\ConnectionException;
use PE\Component\SMTP\Response;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    use PHPMock;

    /**
     * @var string
     */
    private $namespace;

    protected function setUp(): void
    {
        $this->namespace = substr(Connection::class, 0, strrpos(Connection::class, '\\'));
    }

    public function testConstructor(): void
    {
        $connection = new Connection();

        self::assertSame('localhost', $connection->getHost());
        self::assertSame(25, $connection->getPort());
        self::assertFalse($connection->hasSecurity());
        self::assertTrue($connection->getValidate());

        $connection = new Connection('example.com', 587, true, false);

        self::assertSame('example.com', $connection->getHost());
        self::assertSame(587, $connection->getPort());
        self::assertTrue($connection->hasSecurity());
        self::assertFalse($connection->getValidate());
    }

    public function testOpenSkipAlreadyOpened(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $set_error_handler = $this->getFunctionMock($this->namespace, 'set_error_handler');
        $set_error_handler->expects(self::never());

        (new Connection())->open();
    }

    public function testOpenFailureOnCreateSocket1(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $stream_socket_client = $this->getFunctionMock($this->namespace, 'stream_socket_client');
        $stream_socket_client
            ->expects(self::once())
            ->willReturnCallback(static function ($remote_socket, &$errnum = null, &$errstr = null) {
                $errnum = 1;
                $errstr = 'FOO';

                return false;
            });

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('FOO');

        (new Connection())->open();
    }

    public function testOpenFailureOnCreateSocket2(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $stream_socket_client = $this->getFunctionMock($this->namespace, 'stream_socket_client');
        $stream_socket_client
            ->expects(self::once())
            ->willReturnCallback(static function ($remote_socket, &$errnum = null, &$errstr = null) {
                $errnum = 0;

                return false;
            });

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not open socket');

        (new Connection())->open();
    }

    public function testOpenFailureOnCreateSocket3(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $stream_socket_client = $this->getFunctionMock($this->namespace, 'stream_socket_client');
        $stream_socket_client
            ->expects(self::once())
            ->willReturnCallback(static function () {
                trigger_error('ERR', E_WARNING);
            });

        $this->expectException(ConnectionException::class);

        (new Connection())->open();
    }

    public function testOpenFailureOnTimeout(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $stream_socket_client = $this->getFunctionMock($this->namespace, 'stream_socket_client');
        $stream_socket_client->expects(self::once())->willReturn(true);

        $stream_set_timeout = $this->getFunctionMock($this->namespace, 'stream_set_timeout');
        $stream_set_timeout->expects(self::once())->willReturn(false);

        $this->expectException(ConnectionException::class);

        (new Connection())->open();
    }

    public function testOpenSuccess(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $stream_socket_client = $this->getFunctionMock($this->namespace, 'stream_socket_client');
        $stream_socket_client->expects(self::once())->willReturn(true);

        $stream_set_timeout = $this->getFunctionMock($this->namespace, 'stream_set_timeout');
        $stream_set_timeout->expects(self::once())->willReturn(true);

        (new Connection())->open();
    }

    public function testHasEncryptionIfTLSEnabled(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::once())->willReturn(['crypto' => []]);

        self::assertTrue((new Connection())->hasEncryption());
    }

    public function testHasEncryptionIfTLSDisabled(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::once())->willReturn([]);

        self::assertFalse((new Connection())->hasEncryption());
    }

    public function testHasEncryptionIfSSL(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::never());

        self::assertTrue((new Connection('localhost', 25, true))->hasEncryption());
    }

    public function testSetEncryptionFailure(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_socket_enable_crypto');
        $stream_get_meta_data->expects(self::once())->willReturn(false);

        $this->expectException(ConnectionException::class);

        (new Connection())->setEncryption(111);
    }

    public function testSetEncryptionSuccess(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_socket_enable_crypto');
        $stream_get_meta_data->expects(self::once())->willReturn(true);

        (new Connection())->setEncryption(111);
    }

    public function testSetEncryptionIgnore(): void
    {
        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_socket_enable_crypto');
        $stream_get_meta_data->expects(self::never());

        (new Connection('localhost', 25, true))->setEncryption(111);
    }

    public function testSendFailureOnSocket(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $this->expectException(ConnectionException::class);

        (new Connection())->send('FOO');
    }

    public function testSendFailureOnWrite(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fwrite = $this->getFunctionMock($this->namespace, 'fwrite');
        $fwrite->expects(self::once())->willReturn(false);

        $this->expectException(ConnectionException::class);

        (new Connection())->send('FOO');
    }

    public function testSendSuccess(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fwrite = $this->getFunctionMock($this->namespace, 'fwrite');
        $fwrite->expects(self::once())->willReturn(1);

        (new Connection())->send('FOO');
    }

    public function testReadFailureOnSocketCheck(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $this->expectException(ConnectionException::class);

        (new Connection())->read();
    }

    public function testReadFailureOnTimeout(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fgets = $this->getFunctionMock($this->namespace, 'fgets');
        $fgets->expects(self::once())->willReturn('');

        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::once())->willReturn(['timed_out' => true]);

        $this->expectException(ConnectionException::class);

        (new Connection())->read();
    }

    public function testReadFailureOnSocketRead(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fgets = $this->getFunctionMock($this->namespace, 'fgets');
        $fgets->expects(self::once())->willReturn(false);

        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::once())->willReturn(['timed_out' => false]);

        $this->expectException(ConnectionException::class);

        (new Connection())->read();
    }

    public function testReadSuccess(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fgets = $this->getFunctionMock($this->namespace, 'fgets');
        $fgets->expects(self::once())->willReturn('250 OK');

        $stream_get_meta_data = $this->getFunctionMock($this->namespace, 'stream_get_meta_data');
        $stream_get_meta_data->expects(self::once())->willReturn(['timed_out' => false]);

        $stream_set_timeout = $this->getFunctionMock($this->namespace, 'stream_set_timeout');
        $stream_set_timeout->expects(self::once())->with(null, 100);

        $response = (new Connection())->read(100);

        self::assertInstanceOf(Response::class, $response);
    }

    public function testExitNoSocket(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(false);

        $fclose = $this->getFunctionMock($this->namespace, 'fclose');
        $fclose->expects(self::never());

        (new Connection())->exit();
    }

    public function testExitWithSocket(): void
    {
        $is_resource = $this->getFunctionMock($this->namespace, 'is_resource');
        $is_resource->expects(self::once())->willReturn(true);

        $fclose = $this->getFunctionMock($this->namespace, 'fclose');
        $fclose->expects(self::once());

        (new Connection())->exit();
    }
}
