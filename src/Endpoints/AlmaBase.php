<?php

namespace App\Endpoints;

use Alma\API\Endpoints\Base;
use Alma\API\Request;

/**
 * Class AlmaBase
 *
 * @package App\Endpoints
 */
class AlmaBase extends Base
{
    public function request($path): Request
    {
        return parent::request($path);
    }

}
