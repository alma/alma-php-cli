<?php

namespace App\API;

use Alma\API\Client;
use Alma\API\ClientContext;
use App\Tools\Versioning\GitLoader;

/**
 * Class AlmaClient
 *
 * @package App\Client
 */
class AlmaClient extends Client
{
    const PROJECT_NAME = 'ALMA-PHP-CLI';

    public function getContext(): ClientContext
    {
        return $this->context;
    }

    /**
     * @param string    $version
     * @param GitLoader $gitLoader
     *
     * @return void
     */
    public function setVersion(string $version, GitLoader $gitLoader)
    {
        $this->addUserAgentComponent(self::PROJECT_NAME, $version);
        $this->addUserAgentComponent('branch', $gitLoader->getBranchName());
        $this->addUserAgentComponent('commit', $gitLoader->getLastCommit());
    }

}
