<?php

namespace App\Command\Migrations;

use App\Entity\Category;
use App\Entity\Store;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'delete:empty:categories',
    description: 'Date empty categories which doesnt have any products',
)]
class DeleteEmptyCategoryCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $index = 1;
        foreach ($categories as $category) {
            $products = $category->getPrimaryProducts();
            if ($products->count() <= 0) {
                $io->writeln($index . ' Deleting category ' . $category->getName());
                $this->entityManager->remove($category);
                $this->entityManager->flush();
                $index++;
            }
        }

        return Command::SUCCESS;
    }

}
