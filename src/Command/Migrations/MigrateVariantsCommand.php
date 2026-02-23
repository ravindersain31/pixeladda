<?php

namespace App\Command\Migrations;

use App\Entity\ProductType;
use App\Entity\Store;
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
    name: 'migrations:migrate:variants',
    description: 'Migrate products variant from the source database to your application database.',
)]
class MigrateVariantsCommand extends Command
{
    private Connection $sourceConnection;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->sourceConnection = DriverManager::getConnection([
            'url' => $_ENV['DATABASE1_URL'],
        ]);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');
        $store = $this->entityManager->find(Store::class, 1);

        $io->note(sprintf('UPDTING VARIANTS'."\n"));

        $productType = $this->entityManager->getRepository(ProductType::class)->findOneBy(['store' => $store]);

        $productType->setDefaultVariants([
            "0" => "6x18", 
            "1" => "6x24", 
            "3" => "9x12", 
            "4" => "9x24", 
            "5" => "12x12", 
            "6" => "12x18", 
            "7" => "18x12", 
            "8" => "24x18", 
            "9" => "18x24", 
            "10" => "24x24"
        ]);
        $productType->setPricing(
            $this->variantsPrices()
        );

        $productType->setShipping(
            $this->shippingPrices()
        );

        $this->entityManager->persist($productType);
        $this->entityManager->flush();

        $io->success('VARIENT SUCCESSFULLY UPDATED');

        return Command::SUCCESS;
    }

    public function variantsPrices(){
        $prices = '{
            "pricing_6x18": [
                {
                    "aud": "2.29",
                    "qty": "1",
                    "usd": "2.29"
                },
                {
                    "aud": "2.49",
                    "qty": "5",
                    "usd": "2.49"
                },
                {
                    "aud": "2.39",
                    "qty": "10",
                    "usd": "2.39"
                },
                {
                    "aud": "2.29",
                    "qty": "20",
                    "usd": "2.29"
                },
                {
                    "aud": "2.19",
                    "qty": "50",
                    "usd": "2.19"
                },
                {
                    "aud": "2.14",
                    "qty": "100",
                    "usd": "2.14"
                },
                {
                    "aud": "2.09",
                    "qty": "250",
                    "usd": "2.09"
                },
                {
                    "aud": "2.04",
                    "qty": "500",
                    "usd": "2.04"
                },
                {
                    "aud": "1.99",
                    "qty": "1000",
                    "usd": "1.99"
                }
            ],
            "pricing_6x24": [
                {
                    "aud": "4.29",
                    "qty": "1",
                    "usd": "4.29"
                },
                {
                    "aud": "3.59",
                    "qty": "5",
                    "usd": "3.59"
                },
                {
                    "aud": "2.69",
                    "qty": "10",
                    "usd": "2.69"
                },
                {
                    "aud": "1.89",
                    "qty": "20",
                    "usd": "1.89"
                },
                {
                    "aud": "1.79",
                    "qty": "50",
                    "usd": "1.79"
                },
                {
                    "aud": "1.69",
                    "qty": "100",
                    "usd": "1.69"
                },
                {
                    "aud": "1.59",
                    "qty": "250",
                    "usd": "1.59"
                },
                {
                    "aud": "1.49",
                    "qty": "500",
                    "usd": "1.49"
                },
                {
                    "aud": "1.39",
                    "qty": "1000",
                    "usd": "1.39"
                }
            ],
            "pricing_9x12": [
                {
                    "aud": "3.29",
                    "qty": "1",
                    "usd": "3.29"
                },
                {
                    "aud": "3.19",
                    "qty": "5",
                    "usd": "3.19"
                },
                {
                    "aud": "2.69",
                    "qty": "10",
                    "usd": "2.69"
                },
                {
                    "aud": "1.99",
                    "qty": "20",
                    "usd": "1.99"
                },
                {
                    "aud": "1.89",
                    "qty": "50",
                    "usd": "1.89"
                },
                {
                    "aud": "1.79",
                    "qty": "100",
                    "usd": "1.79"
                },
                {
                    "aud": "1.69",
                    "qty": "250",
                    "usd": "1.69"
                },
                {
                    "aud": "1.59",
                    "qty": "500",
                    "usd": "1.59"
                },
                {
                    "aud": "1.49",
                    "qty": "1000",
                    "usd": "1.49"
                }
            ],
            "pricing_9x24": [
                {
                    "aud": "4.79",
                    "qty": "1",
                    "usd": "4.79"
                },
                {
                    "aud": "4.39",
                    "qty": "5",
                    "usd": "4.39"
                },
                {
                    "aud": "3.89",
                    "qty": "10",
                    "usd": "3.89"
                },
                {
                    "aud": "3.19",
                    "qty": "20",
                    "usd": "3.19"
                },
                {
                    "aud": "2.59",
                    "qty": "50",
                    "usd": "2.59"
                },
                {
                    "aud": "2.19",
                    "qty": "100",
                    "usd": "2.19"
                },
                {
                    "aud": "2.89",
                    "qty": "250",
                    "usd": "2.09"
                },
                {
                    "aud": "1.99",
                    "qty": "500",
                    "usd": "1.99"
                },
                {
                    "aud": "1.89",
                    "qty": "1000",
                    "usd": "1.89"
                }
            ],
            "pricing_12x12": [
                {
                    "aud": "5.39",
                    "qty": "1",
                    "usd": "5.39"
                },
                {
                    "aud": "5.19",
                    "qty": "5",
                    "usd": "5.19"
                },
                {
                    "aud": "4.79",
                    "qty": "10",
                    "usd": "4.79"
                },
                {
                    "aud": "2.29",
                    "qty": "20",
                    "usd": "2.29"
                },
                {
                    "aud": "1.89",
                    "qty": "50",
                    "usd": "1.89"
                },
                {
                    "aud": "1.39",
                    "qty": "100",
                    "usd": "1.39"
                },
                {
                    "aud": "1.19",
                    "qty": "250",
                    "usd": "1.19"
                },
                {
                    "aud": "1.09",
                    "qty": "500",
                    "usd": "1.09"
                },
                {
                    "aud": "0.99",
                    "qty": "1000",
                    "usd": "0.99"
                }
            ],
            "pricing_12x18": [
                {
                    "aud": "5.39",
                    "qty": "1",
                    "usd": "5.39"
                },
                {
                    "aud": "5.19",
                    "qty": "5",
                    "usd": "5.19"
                },
                {
                    "aud": "4.79",
                    "qty": "10",
                    "usd": "4.79"
                },
                {
                    "aud": "2.29",
                    "qty": "20",
                    "usd": "2.29"
                },
                {
                    "aud": "1.89",
                    "qty": "50",
                    "usd": "1.89"
                },
                {
                    "aud": "1.49",
                    "qty": "100",
                    "usd": "1.49"
                },
                {
                    "aud": "1.39",
                    "qty": "250",
                    "usd": "1.39"
                },
                {
                    "aud": "1.09",
                    "qty": "500",
                    "usd": "1.09"
                },
                {
                    "aud": "0.99",
                    "qty": "1000",
                    "usd": "0.99"
                }
            ],
            "pricing_18x12": [
                {
                    "aud": "3.59",
                    "qty": "1",
                    "usd": "3.59"
                },
                {
                    "aud": "3.39",
                    "qty": "5",
                    "usd": "3.39"
                },
                {
                    "aud": "3.29",
                    "qty": "10",
                    "usd": "3.29"
                },
                {
                    "aud": "1.79",
                    "qty": "20",
                    "usd": "1.79"
                },
                {
                    "aud": "1.59",
                    "qty": "50",
                    "usd": "1.59"
                },
                {
                    "aud": "1.39",
                    "qty": "100",
                    "usd": "1.39"
                },
                {
                    "aud": "1.19",
                    "qty": "250",
                    "usd": "1.19"
                },
                {
                    "aud": "1.09",
                    "qty": "500",
                    "usd": "1.09"
                },
                {
                    "aud": "0.99",
                    "qty": "1000",
                    "usd": "0.99"
                }
            ],
            "pricing_18x24": [
                {
                    "aud": "4.39",
                    "qty": "1",
                    "usd": "4.39"
                },
                {
                    "aud": "4.29",
                    "qty": "5",
                    "usd": "4.29"
                },
                {
                    "aud": "4.09",
                    "qty": "10",
                    "usd": "4.09"
                },
                {
                    "aud": "3.79",
                    "qty": "20",
                    "usd": "3.79"
                },
                {
                    "aud": "3.29",
                    "qty": "50",
                    "usd": "3.29"
                },
                {
                    "aud": "2.99",
                    "qty": "100",
                    "usd": "2.99"
                },
                {
                    "aud": "2.89",
                    "qty": "250",
                    "usd": "2.89"
                },
                {
                    "aud": "2.79",
                    "qty": "500",
                    "usd": "2.79"
                },
                {
                    "aud": "2.69",
                    "qty": "1000",
                    "usd": "2.69"
                }
            ],
            "pricing_24x18": [
                {
                    "aud": "4.39",
                    "qty": "1",
                    "usd": "4.39"
                },
                {
                    "aud": "4.29",
                    "qty": "5",
                    "usd": "4.29"
                },
                {
                    "aud": "4.09",
                    "qty": "10",
                    "usd": "4.09"
                },
                {
                    "aud": "3.79",
                    "qty": "20",
                    "usd": "3.79"
                },
                {
                    "aud": "3.29",
                    "qty": "50",
                    "usd": "3.29"
                },
                {
                    "aud": "2.99",
                    "qty": "100",
                    "usd": "2.99"
                },
                {
                    "aud": "2.89",
                    "qty": "250",
                    "usd": "2.89"
                },
                {
                    "aud": "2.79",
                    "qty": "500",
                    "usd": "2.79"
                },
                {
                    "aud": "2.69",
                    "qty": "1000",
                    "usd": "2.69"
                }
            ],
            "pricing_24x24": [
                {
                    "aud": "13.99",
                    "qty": "1",
                    "usd": "13.99"
                },
                {
                    "aud": "13.49",
                    "qty": "5",
                    "usd": "13.49"
                },
                {
                    "aud": "11.49",
                    "qty": "10",
                    "usd": "11.49"
                },
                {
                    "aud": "6.49",
                    "qty": "20",
                    "usd": "6.49"
                },
                {
                    "aud": "4.79",
                    "qty": "50",
                    "usd": "4.79"
                },
                {
                    "aud": "4.49",
                    "qty": "100",
                    "usd": "4.49"
                },
                {
                    "aud": "4.39",
                    "qty": "250",
                    "usd": "4.39"
                },
                {
                    "aud": "4.29",
                    "qty": "500",
                    "usd": "4.29"
                },
                {
                    "aud": "4.19",
                    "qty": "1000",
                    "usd": "4.19"
                }
            ]
        }';

        $dataArray = json_decode($prices, true);
        return $dataArray;
    }

    public function shippingPrices(){
        $prices = '{
            "day_1": {
                "day": 1,
                "shipping": {
                    "qty_1": {
                        "aud": "29.99",
                        "qty": "1",
                        "usd": "29.99"
                    },
                    "qty_5": {
                        "aud": "34.99",
                        "qty": "5",
                        "usd": "34.99"
                    },
                    "qty_10": {
                        "aud": "39.99",
                        "qty": "10",
                        "usd": "39.99"
                    },
                    "qty_20": {
                        "aud": "49.99",
                        "qty": "20",
                        "usd": "49.99"
                    },
                    "qty_50": {
                        "aud": "69.99",
                        "qty": "50",
                        "usd": "69.99"
                    },
                    "qty_100": {
                        "aud": "99.99",
                        "qty": "100",
                        "usd": "99.99"
                    },
                    "qty_250": {
                        "aud": "149.99",
                        "qty": "250",
                        "usd": "149.99"
                    },
                    "qty_500": {
                        "aud": "249.99",
                        "qty": "500",
                        "usd": "249.99"
                    },
                    "qty_1000": {
                        "aud": "399.99",
                        "qty": "1000",
                        "usd": "399.99"
                    }
                }
            },
            "day_2": {
                "day": 2,
                "shipping": {
                    "qty_1": {
                        "aud": "24.99",
                        "qty": "1",
                        "usd": "24.99"
                    },
                    "qty_5": {
                        "aud": "29.99",
                        "qty": "5",
                        "usd": "29.99"
                    },
                    "qty_10": {
                        "aud": "34.99",
                        "qty": "10",
                        "usd": "34.99"
                    },
                    "qty_20": {
                        "aud": "39.99",
                        "qty": "20",
                        "usd": "39.99"
                    },
                    "qty_50": {
                        "aud": "59.99",
                        "qty": "50",
                        "usd": "59.99"
                    },
                    "qty_100": {
                        "aud": "79.99",
                        "qty": "100",
                        "usd": "79.99"
                    },
                    "qty_250": {
                        "aud": "129.99",
                        "qty": "250",
                        "usd": "129.99"
                    },
                    "qty_500": {
                        "aud": "199.99",
                        "qty": "500",
                        "usd": "199.99"
                    },
                    "qty_1000": {
                        "aud": "339.99",
                        "qty": "1000",
                        "usd": "339.99"
                    }
                }
            },
            "day_3": {
                "day": 3,
                "shipping": {
                    "qty_1": {
                        "aud": "14.99",
                        "qty": "1",
                        "usd": "14.99"
                    },
                    "qty_5": {
                        "aud": "17.99",
                        "qty": "5",
                        "usd": "17.99"
                    },
                    "qty_10": {
                        "aud": "29.99",
                        "qty": "10",
                        "usd": "29.99"
                    },
                    "qty_20": {
                        "aud": "34.99",
                        "qty": "20",
                        "usd": "34.99"
                    },
                    "qty_50": {
                        "aud": "49.99",
                        "qty": "50",
                        "usd": "49.99"
                    },
                    "qty_100": {
                        "aud": "59.99",
                        "qty": "100",
                        "usd": "59.99"
                    },
                    "qty_250": {
                        "aud": "109.99",
                        "qty": "250",
                        "usd": "109.99"
                    },
                    "qty_500": {
                        "aud": "149.99",
                        "qty": "500",
                        "usd": "149.99"
                    },
                    "qty_1000": {
                        "aud": "229.99",
                        "qty": "1000",
                        "usd": "229.99"
                    }
                }
            },
            "day_4": {
                "day": 4,
                "shipping": {
                    "qty_1": {
                        "aud": "7.99",
                        "qty": "1",
                        "usd": "7.99"
                    },
                    "qty_5": {
                        "aud": "11.99",
                        "qty": "5",
                        "usd": "11.99"
                    },
                    "qty_10": {
                        "aud": "17.99",
                        "qty": "10",
                        "usd": "17.99"
                    },
                    "qty_20": {
                        "aud": "24.99",
                        "qty": "20",
                        "usd": "24.99"
                    },
                    "qty_50": {
                        "aud": "34.99",
                        "qty": "50",
                        "usd": "34.99"
                    },
                    "qty_100": {
                        "aud": "169.99",
                        "qty": "100",
                        "usd": "169.99"
                    },
                    "qty_250": {
                        "aud": "79.99",
                        "qty": "250",
                        "usd": "79.99"
                    },
                    "qty_500": {
                        "aud": "99.99",
                        "qty": "500",
                        "usd": "99.99"
                    }
                }
            }
        }';

        return json_decode($prices, true);
    }
}
