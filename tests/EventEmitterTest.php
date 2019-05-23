<?php

namespace PE\Component\SMTP\Tests;

use PE\Component\SMTP\EventEmitter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventEmitterTest extends TestCase
{
    /**
     * @var EventEmitter
     */
    private $emitter;

    protected function setUp(): void
    {
        $this->emitter = new EventEmitter();
    }

    public function testOnAddSameListenerTwiceWithSamePriority(): void
    {
        $listener = function () {};

        self::assertTrue($this->emitter->attachListener('FOO', $listener));
        self::assertFalse($this->emitter->attachListener('FOO', $listener));
    }

    public function testOnAddSameListenerTwiceWithDiffPriority(): void
    {
        $listener = function () {};

        self::assertTrue($this->emitter->attachListener('FOO', $listener, 10));
        self::assertTrue($this->emitter->attachListener('FOO', $listener, 20));
    }

    public function testOffEmpty(): void
    {
        $listener = function () {};
        static::assertFalse($this->emitter->detachListener('FOO', $listener));
    }

    public function testOffAttached(): void
    {
        $listener1 = function () {};
        $listener2 = function () {};

        $this->emitter->attachListener('FOO', $listener1);
        $this->emitter->attachListener('FOO', $listener2);

        static::assertTrue($this->emitter->detachListener('FOO', $listener1));
        static::assertTrue($this->emitter->detachListener('FOO', $listener2));
    }

    public function testOffDetached(): void
    {
        $listener = function () {};

        $this->emitter->attachListener('FOO', $listener);
        $this->emitter->detachListener('FOO', $listener);

        static::assertFalse($this->emitter->detachListener('FOO', $listener));
    }

    public function testTriggerByPassedPriority(): void
    {
        $value = new \stdClass();
        $value->execution = 0;

        /* @var $listener1 callable|MockObject */
        $listener1 = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener1
            ->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($v) { return $v->execution == 1; }))
            ->willReturnCallback(function ($v) { $v->execution++; });

        /* @var $listener2 callable|MockObject */
        $listener2 = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener2
            ->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($v) { return $v->execution == 0; }))
            ->willReturnCallback(function ($v) { $v->execution++; });

        $this->emitter->attachListener('FOO', $listener1, 1);
        $this->emitter->attachListener('FOO', $listener2, -1);

        $this->emitter->trigger('FOO', $value);

        static::assertSame(2, $value->execution);
    }

    public function testTriggerByDefaultPriority(): void
    {
        $value = new \stdClass();
        $value->execution = 0;

        /* @var $listener1 callable|MockObject */
        $listener1 = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener1
            ->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($v) { return $v->execution == 0; }))
            ->willReturnCallback(function ($v) { $v->execution++; });

        /* @var $listener2 callable|MockObject */
        $listener2 = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener2
            ->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($v) { return $v->execution == 1; }))
            ->willReturnCallback(function ($v) { $v->execution++; });

        $this->emitter->attachListener('FOO', $listener1);
        $this->emitter->attachListener('FOO', $listener2);

        $this->emitter->trigger('FOO', $value);

        static::assertSame(2, $value->execution);
    }

    public function testTriggerEmptyListeners()
    {
        self::assertSame(0, $this->emitter->trigger('BAR'));
    }
}
