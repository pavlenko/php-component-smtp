<?php

namespace PE\Component\SMTP\Module;

use PE\Component\SMTP\ClientInterface;
use PE\Component\SMTP\Event\EventCommand;

final class ModuleDSN implements ModuleInterface
{
    public const RETURN_NONE    = null;
    public const RETURN_HEADERS = 'HDRS';
    public const RETURN_FULL    = 'FULL';

    public const NOTIFY_NONE    = null;
    public const NOTIFY_NEVER   = 'NEVER';
    public const NOTIFY_SUCCESS = 'SUCCESS';
    public const NOTIFY_FAILURE = 'FAILURE';
    public const NOTIFY_DELAY   = 'DELAY';

    /**
     * @var string
     */
    private $return;

    /**
     * @var string
     */
    private $notify;

    /**
     * @param string[]    $notify
     * @param string|null $return
     */
    public function __construct(array $notify = [], ?string $return = null)
    {
        $this->setNotify($notify);
        $this->setReturn($return);
    }

    /**
     * @inheritDoc
     */
    public function getClientListeners(): array
    {
        return [
            [ClientInterface::COMMAND_CREATE, [$this, 'onCommandCreate']]
        ];
    }

    /**
     * @internal
     *
     * @param EventCommand $event
     */
    public function onCommandCreate(EventCommand $event): void
    {
        $command = $event->getCommand();

        switch ($command->getName()) {
            case 'MAIL':
                if ($this->return) {
                    $command->addParam("RET={$this->return}");
                }

                break;
            case 'RCPT':
                if ($this->notify) {
                    $command->addParam("NOTIFY={$this->notify}");
                }

                break;
        }
    }

    /**
     * @return string|null
     */
    public function getReturn(): ?string
    {
        return $this->return;
    }

    /**
     * @param string|null $return
     */
    public function setReturn(?string $return): void
    {
        $modes = [self::RETURN_NONE, self::RETURN_HEADERS, self::RETURN_FULL];

        if (!in_array($return, $modes, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Mode must be one of %s but got %s',
                json_encode($modes),
                json_encode($return)
            ));
        }

        $this->return = $return;
    }

    /**
     * @return array
     */
    public function getNotify(): array
    {
        return $this->notify ? explode(',', $this->notify) : [];
    }

    /**
     * @param string[] $notify
     */
    public function setNotify(array $notify): void
    {
        $options = [
            self::NOTIFY_NONE,
            self::NOTIFY_NEVER,
            self::NOTIFY_SUCCESS,
            self::NOTIFY_FAILURE,
            self::NOTIFY_DELAY,
        ];

        if (array_diff($notify, $options)) {
            throw new \InvalidArgumentException(sprintf(
                'Notify options must one or group of %s values but got %s',
                json_encode($options),
                json_encode($notify)
            ));
        }

        sort($notify);

        if (in_array(self::NOTIFY_NONE, $notify, true)) {
            $this->notify = null;
        } else if (in_array(self::NOTIFY_NEVER, $notify, true)) {
            $this->notify = self::NOTIFY_NEVER;
        } else {
            $this->notify = implode(',', array_unique($notify));
        }
    }
}
