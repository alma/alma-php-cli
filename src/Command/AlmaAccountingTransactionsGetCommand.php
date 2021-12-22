<?php

namespace App\Command;

use App\Command\Meta\AbstractReadAlmaCustomListCommand;

class AlmaAccountingTransactionsGetCommand extends AbstractReadAlmaCustomListCommand
{
    protected static $defaultName        = 'alma:accounting-transactions:get';
    protected static $defaultDescription = 'Call /v1/accounting/transactions endpoint';
    const CUSTOM_ENDPOINT_URI            = '/v1/accounting/transactions';
}
