<?php

namespace App\Command\Migrations;

use App\Entity\CustomerPhotos;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Helper\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:customer-photos-images',
    description: 'Migrate the images data from S3 to another S3 Directory',
)]
class MigrateCustomerPhotosImagesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UploaderHelper $uploaderHelper)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', -1);
        $io = new SymfonyStyle($input, $output);
        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/';
        $data = $this->entityManager->getRepository(CustomerPhotos::class)->findBy([]);
        foreach ($data as $photo) {
            $io->note('Working for #' . $photo->getName());
            $data = $photo->getMigratedData();
            if(!empty($data['photo']) && !$photo->getPhoto()->getName() ){
                $io->comment('Uploading image for #' . $photo->getName());
                $imageUrl = $bucketBase . $data['photo'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $photo->setPhotoFile($file);
                $this->entityManager->persist($photo);
            }else{
                $io->comment('skipping image for #' . $photo->getName());
            }
        }
        $this->entityManager->flush();

        $io->success('Customer photos images migrated');

        return Command::SUCCESS;
    }
}
