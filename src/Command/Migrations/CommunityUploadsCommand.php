<?php

namespace App\Command\Migrations;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommunityUploads;
use App\Entity\Store;
use App\Helper\UploaderHelper;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'migrations:community:uploads',
    description: 'Migrate community uploads photos from the source database to your application database.',
)]
class CommunityUploadsCommand extends Command
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
        $query = "SELECT * FROM sm3_coummunity_uploads";

        $customerPhotos = $this->sourceConnection->fetchAllAssociative($query);
        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/';

        if (!empty($customerPhotos)) {
            $io->section('Photos');
            $io->note(sprintf('Total photos: %s', count($customerPhotos)) . "\n");
            $progressBar = new ProgressBar($output, count($customerPhotos));
            $progressBar->start();
            foreach ($customerPhotos as $key => $photo) {
                $io->success(sprintf('Migrating : %s', $photo['file'], "\n"));
                $cuploads = new CommunityUploads;
                $cuploads->setStore($yspStore);
                $cuploads->setComment($photo['description']);
                $cuploads->setIsEnabled(true);
                $cuploads->setCreatedAt(new \DateTimeImmutable($photo['created_at']));
                $cuploads->setUpdatedAt(new \DateTimeImmutable($photo['updated_at']));
                $cuploads->setMigratedData([
                    'photo' => $photo['file'],
                    'description' => $photo['description'],
                ]);
                // upload images
                $io->note(sprintf('Uploading image: %s', $photo['file']) . "\n");
                $imageUrl = $bucketBase . $photo['file'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $cuploads->setPhotoFile($file);

                $this->entityManager->persist($cuploads);
            }
            $this->entityManager->flush();
        } else {
            $io->warning('No Customer Photos found.');
        }

        $io->success('Migration completed.');

        return Command::SUCCESS;
    }
}
