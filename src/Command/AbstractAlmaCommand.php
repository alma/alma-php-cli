<?php

namespace App\Command;

use Alma\API\Client;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractAlmaCommand extends Command
{
    /** @var Client */
    protected $alma;

    /**
     * @param Client $alma
     * @required
     */
    public function setAlma(Client $alma)
    {
        $this->alma = $alma;
    }

}
