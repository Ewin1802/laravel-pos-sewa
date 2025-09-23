<?php

namespace App\Enums;

enum NavigationGroup: string
{
    case Merchants = 'Merchants';
    case Subscriptions = 'Subscriptions';
    case Payments = 'Payments';
    case Licenses = 'Licenses';
    case System = 'System';

    public function getLabel(): string
    {
        return $this->value;
    }
}
