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
    name: 'migrations:migrate:categories',
    description: 'Migrate Categories from the source database to your application database.',
)]
class MigrateCategoriesCommand extends Command
{
    private Connection $sourceConnection;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = $this->sourceConnection->fetchAllAssociative('SELECT * FROM sm3_desing_categories');
        $store = $this->entityManager->find(Store::class, 1);

        if (!empty($categories)) {
            $io->section('Categories');

            $progressBar = new ProgressBar($output, count($categories));
            $progressBar->start();

            foreach ($categories as $category) {
                $io->text(sprintf('<fg=green>%s</>', $category['name']));
                $cat = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $category['slug']]);
                if (!$cat instanceof Category) {
                    $cat = new Category;
                    $cat->setName($category['name']);
                    if (!empty($category['slug'])) {
                        $cat->setSlug($category['slug']);
                    } else {
                        $cat->setSlug(str_replace(' ', '-', $category['name']));
                    }
                } else {
                    $io->warning(sprintf('Category with slug "%s" already exists. Updating...', $category['slug']));
                }

                if ($store instanceof Store) {
                    $cat->setStore($store);
                }

                $cat->setSeoMeta([
                    "title" => substr($category['name'], 0, 255),
                    "keywords" => substr($category['name'], 0, 255),
                    "description" => substr($category['name'], 0, 255)
                ]);

                $cat->setSortPosition($category['sort_order']);
                $cat->setSkuInitial($category['sku_initials']);

                $cat->setIsEnabled($category['status'] == 1);

                $cat->setOldCategoryId($category['category_id']);
                $cat->setMigratedData([
                    'categoryId' => $category['category_id'],
                    'parentCategoryId' => $category['parent_category_id'],
                    'desktopBanner' => $category['image'],
                    'mobileBanner' => $category['mobile_image'],
                    'thumbnail' => $category['cover_image'],
                ]);

                $this->entityManager->persist($cat);

                $progressBar->advance();
                $io->newLine();
            }

            $this->entityManager->flush();

            $progressBar->finish();
            $io->newLine();

        } else {
            $io->warning('No categories found.');
        }

        $io->success('Categories listing completed.');

        return Command::SUCCESS;
    }

}
