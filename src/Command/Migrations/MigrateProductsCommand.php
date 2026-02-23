<?php

namespace App\Command\Migrations;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Entity\Store;
use App\Helper\SKUGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'migrations:migrate:products',
    description: 'Migrate products from the source database to your application database.'
)]
class MigrateProductsCommand extends Command
{
    private Connection $sourceConnection;
    private EntityManagerInterface $entityManager;
    private SKUGenerator $skuGenerator;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SKUGenerator $skuGenerator, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->skuGenerator = $skuGenerator;
        $this->slugger = $slugger;
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', -1);
        $io = new SymfonyStyle($input, $output);
        $yspStore = $this->entityManager->find(Store::class, 1);
        $productType = $this->entityManager->find(ProductType::class, 1);

        // fix product 
        $queryToFixproduct = "UPDATE `sm3_template_designs` SET `template_product_option_size_value_id` = '70' WHERE `sm3_template_designs`.`id` = 5486;";
        $this->sourceConnection->fetchAllAssociative($queryToFixproduct);

        $query = "SELECT *
                FROM sm3_template_designs AS a LEFT JOIN sm3_desing_categories AS c ON a.template_category = c.category_id
                WHERE a.category_id=1 AND template_product_option_size_value_id=71
                ;";

        $products = $this->sourceConnection->fetchAllAssociative($query);

        if (!empty($products)) {
            $io->section('Products');
            $io->note(sprintf('Total Products: %s', count($products)) . "\n");
            $progressBar = new ProgressBar($output, count($products));
            $progressBar->start();

            foreach ($products as $key => $product) {
                if (in_array($product['sku'], ['CM00001', 'SC0001'])) {
                    continue;
                }

                $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy(['oldCategoryId' => $product['template_category']]);
                if ($existingCategory === null) {
                    $io->warning('Category Not Found. Skipping...');
                    continue;
                }

                $io->success(sprintf('CREATING NEW PRODUCT SKU : %s', $product['sku'], "\n"));

                $pro = $this->entityManager->getRepository(Product::class)->findOneBy(['sku' => $product['sku']]);
                if ($pro instanceof Product) {
                    continue;
                }

                $pro = new Product();
                $pro->setStore($yspStore);

                $pro->setProductType($productType);
                $pro->setSku($product['sku']);


                $pro->setName($product['title']);
                $pro->setSlug(strtolower($this->slugger->slug($product['title'])));

                $pro->setSeoMeta([
                    "title" => $product['title'],
                    "keywords" => $product['template_tags'],
                    "description" => $product['template_tags']
                ]);

                $pro->setHasVariant(true);
                $pro->setPrimaryCategory($existingCategory);
                $pro->setIsEnabled($product['status'] == 1);
                $pro->setModalName($key);

                $pro->setMigratedData([
                    'templateId' => $product['id'],
                    'templateParentId' => $product['template_partent_id'],
                    'templateJson' => $product['template_json'],
                    'templateThumb' => $product['template_thumb'],
                    'displayThumb' => $product['display_thumb'],
                    'seoThumb' => $product['seo_thumb'],
                    'templateTags' => $product['template_tags'],
                ]);

                $this->entityManager->persist($pro);
                $this->entityManager->flush();

                $this->addVariants($pro, $io);

                $progressBar->advance();
            }

            $this->entityManager->flush();
            $progressBar->finish();
            $io->success('Migration completed.');
        } else {
            $io->error('No product data found in the source database.');
        }

        return Command::SUCCESS;
    }

    protected function addVariants(Product $product, $io): void
    {
        $oldTemplateId = $product->getMigratedData()['templateId'];

        $variants = $product->getVariants();
        if ($variants->count() > 0) {
            $io->error('Product already has variants.');
            $product->removeVariant($product);
            $io->info('Variant Removed.');
            $this->entityManager->persist($product);
            $this->entityManager->flush();
        }

        $productType = $product->getProductType();
        foreach ($productType->getDefaultVariants() as $variantName) {

            $getOptionValueId = $this->updateVarientTemplate($variantName);
            $query = "SELECT * FROM sm3_template_designs WHERE template_product_option_size_value_id = $getOptionValueId and template_partent_id = $oldTemplateId";
            $productData = $this->sourceConnection->fetchAssociative($query);
            if (empty($productData)) {
                $query = "SELECT * FROM sm3_template_designs WHERE template_product_option_size_value_id = $getOptionValueId and id = $oldTemplateId";
                $productData = $this->sourceConnection->fetchAssociative($query);
            }

            $variant = new Product();
            $variant->setParent($product);
            $variant->setName($variantName);
            $variant->setSlug(strtolower($this->slugger->slug($variant->getName())));

            $variant->setMigratedData([
                'templateId' => $productData['id'],
                'templateParentId' => $productData['template_partent_id'] ?? $productData['id'],
                'templateJson' => $productData['template_json'],
                'templateThumb' => $productData['template_thumb'],
                'displayThumb' => $productData['display_thumb'],
                'seoThumb' => $productData['seo_thumb'],
                'templateTags' => $productData['template_tags'],
            ]);

            $sku = $this->skuGenerator->generateVariant($product);
            $io->note(sprintf('ADDING NEW VARIANT SKU: %s', $sku, "\n"));
            $variant->setSku($sku);
            $variant->setIsEnabled(true);
            $product->addVariant($variant);
            $this->entityManager->flush();
        }
    }

    protected function updateVarientTemplate($variantName)
    {

        $data = [
            [
                'optionValueId' => 2,
                'size' => '6x18'
            ],
            [
                'optionValueId' => 3,
                'size' => '6x24'
            ],
            [
                'optionValueId' => 4,
                'size' => '9x12'
            ],
            [
                'optionValueId' => 5,
                'size' => '12x18'
            ],
            [
                'optionValueId' => 6,
                'size' => '18x12'
            ],
            [
                'optionValueId' => 66,
                'size' => '9x24'
            ],
            [
                'optionValueId' => 69,
                'size' => '18x24'
            ],
            [
                'optionValueId' => 70,
                'size' => '24x18'
            ],
            [
                'optionValueId' => 71,
                'size' => '24x24'
            ],
            [
                'optionValueId' => 96,
                'size' => '12x12'
            ]
        ];

        foreach ($data as $item) {
            if ($item['size'] == $variantName) {
                return $item['optionValueId'];
            }
        }
        return 71;
    }

}