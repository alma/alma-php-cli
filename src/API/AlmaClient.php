<?php

namespace App\API;

use Alma\API\Client;
use Alma\API\ClientContext;

/**
 * Class AlmaClient
 *
 * @package App\Client
 */
class AlmaClient extends Client
{
    public function getContext(): ClientContext
    {
        return $this->context;
    }

}
