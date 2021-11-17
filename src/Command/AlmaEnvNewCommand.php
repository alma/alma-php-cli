<?php

namespace App\Command;

use App\Service\EnvBuilder;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AlmaEnvNewCommand extends Command
{
    const TEST_MODE = 'test';
    const LIVE_MODE = 'live';
    const MODES     = [
        self::TEST_MODE,
        self::LIVE_MODE,
    ];
    const URLS      = [
        self::TEST_MODE => 'https://api.sandbox.getalma.eu',
        self::LIVE_MODE => 'https://api.getalma.eu',
    ];
    protected static $defaultName = 'alma:env:new';
    protected static $defaultDescription = 'create new alma environments files (.env.<ENV>live.local and .env.<ENV>test.local) with required variables';
    private EnvBuilder $builder;
    private SymfonyStyle $io;

    /**
     * @param string $mode
     *
     * @return mixed
     */
    private function askApiKey(string $mode)
    {
        return $this->io->ask(sprintf('give me the %s api key please', $mode));
    }

    /**
     * @param string $mode
     *
     * @return mixed
     */
    private function askApiMode(string $mode)
    {
        return $this->io->choice(sprintf('choose the %s mode please', $mode), self::MODES, $mode);
    }

    /**
     * @param string $mode
     *
     * @return mixed
     */
    private function askApiUrl(string $mode)
    {
        return $this->io->choice(sprintf('choose the %s api url please', $mode), array_values(self::URLS), self::URLS[$mode]);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('env_name', InputArgument::REQUIRED, 'The <ENV> name')
            ->addOption(
                'test-only',
                't',
                InputOption::VALUE_NONE,
                'build only test env (cannot be used with --live-only)'
            )
            ->addOption(
                'live-only',
                'l',
                InputOption::VALUE_NONE,
                'build only live env (cannot be used with --test-only)'
            )
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'overwrite file(s) if exits')
        ;
    }

    /**
     * @param        $envName
     * @param string $mode
     *
     * @throws Exception
     */
    private function createEnv($envName, string $mode): void
    {
        $this->io->title(sprintf('Building %s %s env', strtoupper($envName), $mode));
        $this->builder->createEnv(
            $envName,
            $mode,
            $this->askApiKey($mode),
            $this->askApiMode($mode),
            $this->askApiUrl($mode)
        );
    }

    /**
     * @throws Exception
     */
    private function createLiveEnv(string $envName)
    {
        $this->createEnv($envName, self::LIVE_MODE);
    }

    /**
     * @throws Exception
     */
    private function createTestEnv($envName)
    {
        $this->createEnv($envName, self::TEST_MODE);
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $testOnly  = $input->getOption('test-only');
        $liveOnly  = $input->getOption('live-only');
        $overwrite = $input->getOption('overwrite');
        $this->builder->setOverwrite($overwrite);
        if ($testOnly && $liveOnly) {
            throw new Exception('--test-only and --live-only are exclusive options');
        }
        $envName = strtolower($input->getArgument('env_name'));
        if ($testOnly) {
            $this->createTestEnv($envName);

            return self::SUCCESS;
        }
        if ($liveOnly) {
            $this->createLiveEnv($envName);

            return self::SUCCESS;
        }
        $this->createTestEnv($envName);
        $this->createLiveEnv($envName);

        return Command::SUCCESS;
    }

    /**
     * @param EnvBuilder $builder
     *
     * @required
     */
    public function setBuilder(EnvBuilder $builder)
    {
        $this->builder = $builder;
    }
}
