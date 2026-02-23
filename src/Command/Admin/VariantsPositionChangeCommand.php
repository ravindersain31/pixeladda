<?php

namespace App\Command\Admin;

use App\Entity\Product;
use App\Entity\Store;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'admin:product:variant:position',
    description: 'Update variant positions based on width',
)]
class VariantsPositionChangeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $positions = Product::VARIANTS;

        foreach ($positions as $name => $position) {
            $numberOfRecordUpdated = $this->entityManager->getRepository(Product::class)->updatePosition($name, $position);
            $io->writeln(sprintf('Updated %s records for %s', $numberOfRecordUpdated, $name));
        }
        return Command::SUCCESS;
    }
}
