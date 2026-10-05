<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum DeploymentTrigger: string implements VitoEnum
{
    case MANUAL = 'manual';
    case API = 'api';
    case WEBHOOK = 'webhook';
    case WORKFLOW = 'workflow';

    public function getColor(): string
    {
        return match ($this) {
            self::MANUAL => 'gray',
            self::API => 'info',
            self::WEBHOOK => 'success',
            self::WORKFLOW => 'warning',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
