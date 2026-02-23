<?php

namespace App\Command\Migrations;

use App\Entity\CustomerPhotos;
use App\Entity\Store;
use App\Helper\UploaderHelper;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'migrations:migrate:customer-photos',
    description: 'Migrate customer photos from the source database to your application database.',
)]
class MigrateCustomerPhotosCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UploaderHelper $uploaderHelper, private Connection $sourceConnection)
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $yspStore = $this->entityManager->find(Store::class, 1);
        $query = "SELECT * FROM sm3_customer_photos";

        $customerPhotos = $this->sourceConnection->fetchAllAssociative($query);

        if(!empty($customerPhotos)){
            $io->section('Photos');
            $io->note(sprintf('Total photos: %s', count($customerPhotos)) . "\n");
            $progressBar = new ProgressBar($output, count($customerPhotos));
            $progressBar->start();
            foreach($customerPhotos as $key => $photo){
                $io->success(sprintf('Migrating : %s', $photo['photo'], "\n"));
                $cphotos = new CustomerPhotos;
                $cphotos->setStore($yspStore);
                $cphotos->setName($photo['name']);
                $cphotos->setComment($photo['comment']);
                $cphotos->setIsEnabled($photo['status'] ? true : false);
                $cphotos->setCreatedAt(new \DateTimeImmutable($photo['created_at']));
                $cphotos->setUpdatedAt(new \DateTimeImmutable($photo['updated_at']));
                $cphotos->setMigratedData([
                    'photo' => $photo['photo'],
                    'name' => $photo['name'],
                    'comment' => $photo['comment'],
                    'status' => $photo['status'],
                ]);
                $this->entityManager->persist($cphotos);
            }
            $this->entityManager->flush();
        }else{
            $io->warning('No Customer Photos found.');
        }

        $io->success('Migration completed.');

        return Command::SUCCESS;
    }
}
