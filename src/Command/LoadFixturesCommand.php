<?php

namespace App\Command;

use App\DataFixtures\UserFixtures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends Command
{
    protected static $defaultName = 'app:load-fixtures';
    
    private UserFixtures $userFixtures;

    public function __construct(UserFixtures $userFixtures)
    {
        parent::__construct();
        $this->userFixtures = $userFixtures;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->userFixtures->load();
        $output->writeln('Fixtures loaded successfully!');
        return Command::SUCCESS;
    }
}