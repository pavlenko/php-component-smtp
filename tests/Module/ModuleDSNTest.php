<?php

namespace PE\Component\SMTP\Tests\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Command;
use PE\Component\SMTP\Event\EventCommand;
use PE\Component\SMTP\Module\ModuleDSN;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModuleDSNTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
    }

    public function testGetClientListeners(): void
    {
        $module = new ModuleDSN();

        $expected = [
            [ClientInterface::COMMAND_CREATE, [$module, 'onCommandCreate']]
        ];

        self::assertSame($expected, $module->getClientListeners());
    }

    public function testOnCommandMAILCreateIfNotConfigured(): void
    {
        $command = new Command('MAIL');

        $module = new ModuleDSN();
        $module->onCommandCreate(new EventCommand($command, $this->client));

        self::assertFalse((bool) strpos((string) $command, 'RET=FULL'));
    }

    public function testOnCommandMAILCreateIfConfigured(): void
    {
        $command = new Command('MAIL');

        $module = new ModuleDSN([], ModuleDSN::RETURN_FULL);
        $module->onCommandCreate(new EventCommand($command, $this->client));

        self::assertTrue((bool) strpos((string) $command, 'RET=FULL'));
    }

    public function testOnCommandRCPTCreateIfNotConfigured(): void
    {
        $command = new Command('RCPT');

        $module = new ModuleDSN();
        $module->onCommandCreate(new EventCommand($command, $this->client));

        self::assertFalse((bool) strpos((string) $command, 'NOTIFY=SUCCESS'));
    }

    public function testOnCommandRCPTCreateIfConfigured(): void
    {
        $command = new Command('RCPT');

        $module = new ModuleDSN([ModuleDSN::NOTIFY_SUCCESS]);
        $module->onCommandCreate(new EventCommand($command, $this->client));

        self::assertTrue((bool) strpos((string) $command, 'NOTIFY=SUCCESS'));
    }

    public function testSetReturnFailure(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $module = new ModuleDSN();
        $module->setReturn('INVALID');
    }

    public function testSetReturnSuccess(): void
    {
        $module = new ModuleDSN();
        $module->setReturn(ModuleDSN::RETURN_FULL);

        self::assertSame(ModuleDSN::RETURN_FULL, $module->getReturn());
    }

    public function testSetNotifyFailure(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $module = new ModuleDSN();
        $module->setNotify(['INVALID']);
    }

    public function testSetNotifySuccessMulti(): void
    {
        $notify = [ModuleDSN::NOTIFY_SUCCESS, ModuleDSN::NOTIFY_FAILURE];

        $module = new ModuleDSN();
        $module->setNotify($notify);

        sort($notify);

        self::assertSame($notify, $module->getNotify());
    }

    public function testSetNotifySuccessNever(): void
    {
        $notify = [ModuleDSN::NOTIFY_NEVER, ModuleDSN::NOTIFY_FAILURE];

        $module = new ModuleDSN();
        $module->setNotify($notify);

        sort($notify);

        self::assertSame([ModuleDSN::NOTIFY_NEVER], $module->getNotify());
    }

    public function testSetNotifySuccessUnset(): void
    {
        $notify = [ModuleDSN::NOTIFY_NONE, ModuleDSN::NOTIFY_FAILURE];

        $module = new ModuleDSN();
        $module->setNotify($notify);

        sort($notify);

        self::assertSame([], $module->getNotify());
    }
}
