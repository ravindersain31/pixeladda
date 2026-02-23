<?php

namespace App\Command\Migrations;

use App\Entity\Category;
use App\Helper\UploaderHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:category-images',
    description: 'Migrate the template data from S3 to another S3 Directory',
)]
class MigrateCategoryImagesCommand extends Command
{

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UploaderHelper $uploaderHelper)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/';
        $categories = $this->entityManager->getRepository(Category::class)->findBy([]);
        foreach ($categories as $category) {
            $io->note('Working for #' . $category->getName());

            $data = $category->getMigratedData();

            if (!empty($data['thumbnail']) && !$category->getThumbnail()->getName()) {
                $io->comment('Uploading Thumbnail for #' . $category->getName());
                $imageUrl = $bucketBase . $data['thumbnail'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setThumbnailFile($file);
            }


            if (!empty($data['desktopBanner']) && !$category->getBanner()->getName()) {
                $io->comment('Uploading Desktop Banner for #' . $category->getName());
                $imageUrl = $bucketBase . $data['desktopBanner'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setBannerFile($file);
            }

            if (!empty($data['mobileBanner']) && !$category->getMobileBanner()->getName()) {
                $io->comment('Uploading Mobile Banner for #' . $category->getName());
                $imageUrl = $bucketBase . $data['mobileBanner'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setMobileBannerFile($file);
            }

            if (!empty($data['promoThumbnail']) && !$category->getPromoThumbnail()->getName()) {
                $io->comment('Uploading Promo Thumbnail for #' . $category->getName());
                $imageUrl = $bucketBase . $data['promoThumbnail'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setPromoThumbnailFile($file);
            }

            if (!empty($data['desktopPromoBanner']) && !$category->getPromoBanner()->getName()) {
                $io->comment('Uploading Desktop Promo Banner for #' . $category->getName());
                $imageUrl = $bucketBase . $data['desktopPromoBanner'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setPromoBannerFile($file);
            }

            if (!empty($data['mobilePromoBanner']) && !$category->getPromoMobileBanner()->getName()) {
                $io->comment('Uploading Mobile Promo Banner for #' . $category->getName());
                $imageUrl = $bucketBase . $data['mobilePromoBanner'];
                $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
                $category->setPromoMobileBannerFile($file);
            }

            $this->entityManager->persist($category);

            $this->entityManager->flush();

        }
        return Command::SUCCESS;
    }

}
