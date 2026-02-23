<?php

namespace App\Command\Migrations;

use App\Entity\Product;
use App\Entity\ProductType;
use App\Entity\Store;
use App\Helper\UploaderHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:template',
    description: 'Migrate the template data from S3 to another S3 Directory',
)]
class MigrateTemplateCommand extends Command
{

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UploaderHelper $uploaderHelper)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketBase = 'https://yardsignplus-static.s3.amazonaws.com/';
        $products = $this->entityManager->getRepository(Product::class)->findBy(['parent' => null]);
        $i = 1;
        foreach ($products as $product) {
            $variants = $product->getVariants();
            $io->note($i . ': Working for #' . $product->getSku());
            foreach ($variants as $variant) {
                $isTemplateExists = $variant->getMetaDataKey('templateJson');
                $io->comment('Uploading template for #' . $variant->getName());
                if (empty($isTemplateExists)) {
                    $data = $variant->getMigratedData();
                    $template = $bucketBase . $data['templateJson'];
                    $file = $this->uploaderHelper->getUploadedFileFromUrl($template);
                    $this->uploaderHelper->setUploadNamePrefix($variant->getSlug());
                    $this->uploaderHelper->setUploadPath($product->getSku());
                    $filePath = $this->uploaderHelper->upload($file, 'productTemplateStorage', false);
                    $variant->setMetaDataKey('templateJson', $filePath);
                    $this->entityManager->persist($variant);
                    $this->entityManager->flush();
                }
            }
            $io->success($i . ': Template migrated for #' . $product->getSku());
            $i++;
        }
        return Command::SUCCESS;
    }

}
