<?php

namespace App\Service;

use Exception;

/**
 * Class EnvBuilder
 *
 * @package App\Service
 */
class EnvBuilder
{
    private bool $overwrite = false;

    /**
     * @param string $envName
     * @param string $type
     *
     * @return string
     */
    public function getFileName(string $envName, string $type): string
    {
        return sprintf('.env.%s%s.local', $envName, $type);
    }

    /**
     * @throws Exception
     */
    public function createEnv(string $envName, string $type, string $apiKey, string $mode, string $url)
    {
        $fileName = $this->getFileName($envName, $type);
        if (file_exists($fileName) && !$this->overwrite) {
            throw new Exception(sprintf('file %s already exists !', $fileName));
        }
        $content = sprintf("ALMA_API_KEY='%s'\nALMA_API_MODE='%s'\nALMA_API_URL='%s'\n", $apiKey, $mode, $url);
        file_put_contents($fileName, $content);
    }

    /**
     * @param bool $overwrite
     */
    public function setOverwrite(bool $overwrite)
    {
        $this->overwrite = $overwrite;
    }

}
