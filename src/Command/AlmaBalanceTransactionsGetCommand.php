<?php

namespace App\Command;

use App\Command\Meta\AbstractReadAlmaCustomListCommand;

class AlmaBalanceTransactionsGetCommand extends AbstractReadAlmaCustomListCommand
{
    protected static $defaultName        = 'alma:balance-transactions:get';
    protected static $defaultDescription = 'Call /v1/balance-transactions endpoint';
    const CUSTOM_ENDPOINT_URI            = '/v1/balance-transactions';
}
