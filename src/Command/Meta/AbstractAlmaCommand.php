<?php

namespace App\Command\Meta;

use App\API\AlmaClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Class AbstractAlmaCommand
 *
 * @package App\Command
 */
abstract class AbstractAlmaCommand extends Command
{
    public const INPUT_OPTION_REQUIRED_ARRAY = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED;
    protected ?AlmaClient $almaClient = null;
    protected ?SymfonyStyle $io = null;
    private ContainerInterface $container;

    /**
     * @return array
     */
    protected function getAvailableEnvs(): array
    {
        $availableEnvs = [];
        $projectDir    = $this->container->getParameter('kernel.project_dir');

        $liveRegex = sprintf("#^%s/\.env\.(lang)?(.*)(live|test|dev)\.local$#", $projectDir);
        foreach (glob($projectDir . '/.env.*.local') as $envFile) {
            if (preg_match($liveRegex, $envFile, $matches)) {
                $envKey         = sprintf(
                    "%s%s-%s",
                    $matches[1] ? $matches[1] . "-" : "",
                    $matches[2],
                    $matches[3]
                );
                $availableEnvs[$envKey] = $envFile;
            }
        }

        return $availableEnvs;
    }

    /**
     * Initialize Input Output SymfonyStyle Property
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initIo(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->io) {
            $this->io = new SymfonyStyle($input, $output);
        }
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->initIo($input, $output);
        if (isset($_ENV['ALMA_API_KEY']) && $_ENV['ALMA_API_KEY']) {
            $this->almaClient = $this->container->get(AlmaClient::class);
        }
        if (!$this->almaClient && ! $input->getOption('no-interaction') && $availableEnvs = $this->getAvailableEnvs()) {
            $question   = 'Alma Client not fully loaded, please select an environment to load it';
            $env        = $this->io->choice($question, array_keys($availableEnvs));
            (new Dotenv())->load($availableEnvs[$env]);
            $this->almaClient = $this->container->get(AlmaClient::class);
        }
        if (!$this->almaClient) {
            throw new \Exception('Alma Client is not instantiated.');
        }

        return parent::run($input, $output);
    }

    /**
     * @param ContainerInterface $container
     *
     * @required
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

}
