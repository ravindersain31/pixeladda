<?php

namespace App\Command\Migrations;

use App\Entity\Category;
use App\Entity\Product;
use App\Helper\UploaderHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:product-images',
    description: 'Migrate the template data from S3 to another S3 Directory',
)]
class MigrateProductImagesCommand extends Command
{

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UploaderHelper $uploaderHelper)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $products = $this->entityManager->getRepository(Product::class)->findBy(['parent' => null], []);
        $i = 1;
        foreach ($products as $product) {
            $io->note($i . ': Working for #' . $product->getSku());

            $this->uploadImages($product, $io);

            foreach ($product->getVariants() as $variant) {
                $this->uploadImages($variant, $io);
            }
            $io->success($i . ': Finished for #' . $product->getSku());
            $i++;
        }

        return Command::SUCCESS;
    }

    private function uploadImages(Product $product, $io): void
    {
        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/';

        $data = $product->getMigratedData();

        if (!empty($data['templateThumb']) && !$product->getImage()->getName()) {
            $io->comment($product->getSku() . ': Uploading Image for #' . $product->getName());
            $imageUrl = $bucketBase . $data['templateThumb'];
            $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
            $product->setImageFile($file);
        }

        if (!empty($data['seoThumb']) && !$product->getSeoImage()->getName()) {
            $io->comment($product->getSku() . ': Uploading Seo Image for #' . $product->getName());
            $imageUrl = $bucketBase . $data['seoThumb'];
            $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
            $product->setSeoImageFile($file);
        }

        if (!empty($data['displayThumb']) && !$product->getDisplayImage()->getName()) {
            $io->comment($product->getSku() . ': Uploading Display Image for #' . $product->getName());
            $imageUrl = $bucketBase . $data['displayThumb'];
            $file = $this->uploaderHelper->getUploadedFileFromUrl($imageUrl);
            $product->setDisplayImageFile($file);
        }

        $this->entityManager->persist($product);

        $this->entityManager->flush();
    }

}
