<?php

namespace App\Tools\Versioning;


use function is_array;

class GitLoader
{
    /**
     * @var string
     */
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @return string
     */
    public function getBranchName(): string
    {
        $gitHeadFile = $this->projectDir . '/.git/HEAD';
        $branchName  = 'no branch name';

        $stringFromFile = file_exists($gitHeadFile) ? file($gitHeadFile, FILE_USE_INCLUDE_PATH) : "";

        if (is_array($stringFromFile)) {
            //get the string from the array
            $firstLine = $stringFromFile[0];
            //separate out by the "/" in the string
            $explodedString = explode("/", $firstLine, 3);

            $branchName = trim($explodedString[2]);
        }

        return $branchName;
    }

    /**
     * @return string
     */
    public function getLastCommit(): string
    {
        $file   = $this->projectDir . '/.git/refs/heads/' . $this->getBranchName();
        $commit = file_exists($file) ? file($file, FILE_USE_INCLUDE_PATH) : "not found";
        return is_array($commit) ? trim($commit[0]) : "";
    }

    /**
     * @return string
     */
    public function getLastCommitMessage(): string
    {
        $file          = $this->projectDir . '/.git/COMMIT_EDITMSG';
        $commitMessage = file_exists($file) ? file($file, FILE_USE_INCLUDE_PATH) : "";

        return is_array($commitMessage) ? trim($commitMessage[0]) : "";
    }

    /**
     * @return array
     */
    public function getLastCommitDetail(): array
    {
        $logs       = [];
        $gitLogFile = $this->projectDir . '/.git/logs/HEAD';
        $gitLogs    = file_exists($gitLogFile) ? file($gitLogFile, FILE_USE_INCLUDE_PATH) : "";

        $logExploded    = explode(' ', end($gitLogs));
        $logs['author'] = $logExploded[2] ?? 'not defined';
        $logs['date']   = isset($logExploded[4]) ? date('Y/m/d H:i', $logExploded[4]) : "not defined";

        return $logs;
    }
}

