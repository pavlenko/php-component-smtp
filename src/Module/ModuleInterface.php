<?php

namespace PE\Component\SMTP\Module;

interface ModuleInterface
{
    /**
     * @return array
     */
    public function getClientListeners(): array;
}
