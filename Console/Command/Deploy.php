<?php
namespace Ssquare\Github\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Ssquare\Github\Model\Config;

class Deploy extends Command
{
    const INPUT_KEY_BRANCH = 'branch';
    const INPUT_KEY_COMMIT_MESSAGE = 'commit_message';

    private $config;
    private $state;

    public function __construct(Config $config, State $state)
    {
        $this->config = $config;
        $this->state = $state;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('git:deploy')
            ->setDescription('Deploy the current branch with Git')
            ->addArgument(self::INPUT_KEY_BRANCH, InputArgument::REQUIRED, 'Branch name')
            ->addArgument(self::INPUT_KEY_COMMIT_MESSAGE, InputArgument::REQUIRED, 'Commit message');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $branch = $input->getArgument(self::INPUT_KEY_BRANCH);
        $commitMessage = $input->getArgument(self::INPUT_KEY_COMMIT_MESSAGE);
        $githubToken = $this->config->getGithubToken();
        $githubRepoUrl = $this->config->getGithubRepoUrl();

        if (!$githubToken || !$githubRepoUrl) {
            $io->error('GitHub token or repository URL is not configured.');
            return Command::FAILURE;
        }

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        // Add changes to git
        $io->section('Adding changes to git...');
        $this->executeShellCommand('git add .', $io);

        // Commit changes
        $io->section('Committing changes...');
        $this->executeShellCommand(sprintf('git commit -m "%s"', $commitMessage), $io);

        // Set up remote URL with token
        $io->section('Configuring remote URL...');
        $remoteUrl = sprintf('https://%s@%s', $githubToken, parse_url($githubRepoUrl, PHP_URL_HOST) . parse_url($githubRepoUrl, PHP_URL_PATH));
        $this->executeShellCommand(sprintf('git remote set-url origin %s', $remoteUrl), $io);

        // Verify the branch exists on the remote
        $io->section('Verifying remote branch...');
        $branches = $this->executeShellCommand('git ls-remote --heads origin', $io);
        if (strpos($branches, "refs/heads/$branch") === false) {
            $io->error(sprintf('Branch "%s" does not exist on the remote repository.', $branch));
            return Command::FAILURE;
        }

        // Pull the latest changes from the specified branch
        $io->section('Pulling latest changes from the remote branch...');
        $this->executeShellCommand(sprintf('git pull origin %s', $branch), $io);

        // Push changes to the specified branch
        $io->section('Pushing changes to the remote branch...');
        $this->executeShellCommand(sprintf('git push origin %s', $branch), $io);
        

        $io->success('Deployment completed successfully.');
        return Command::SUCCESS;
    }

    private function executeShellCommand($command, SymfonyStyle $io)
    {
        $output = [];
        $returnVar = null;
        exec($command . ' 2>&1', $output, $returnVar);
        $outputText = implode("\n", $output);

        if ($returnVar !== 0) {
            $io->error(sprintf('Command "%s" failed with error: %s', $command, $outputText));
            throw new \RuntimeException(sprintf('Command "%s" failed.', $command));
        }

        $io->success(sprintf('Command "%s" executed successfully.', $command));
        return $outputText;
    }
}
