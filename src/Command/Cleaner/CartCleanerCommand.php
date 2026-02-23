<?php

namespace App\Command\Cleaner;

use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'cleaner:cart',
    description: 'Clean the cart table',
)]
class CartCleanerCommand extends Command
{

    public function __construct(private readonly LockFactory $lockFactory, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lock = $this->lockFactory->createLock('cleaner:cart', ttl: 120);
        if (!$lock->acquire()) {
            $io->error('The command is already running in another process.');
            return Command::FAILURE;
        }
        try {
            try {
                $deleteCartWithZeroTotal = $this->entityManager->getRepository(Cart::class)->deleteCartWithZeroTotal(3)->execute();
                $io->comment(sprintf('%d carts has been deleted which are older than 3 days with no items.', $deleteCartWithZeroTotal));
            } catch (\Exception $e) {
                $deleteCartWithZeroTotal = $this->entityManager->getRepository(Cart::class)->deleteCartWithZeroTotal(3, false)->getResult();
                $i = 0;
                /** @var Cart $cart */
                foreach ($deleteCartWithZeroTotal as $cart) {
                    $io->comment(sprintf('%d: Cart %d has been deleted.', $i, $cart->getId()));
                    foreach ($cart->getCartItems() as $item) {
                        $this->entityManager->remove($item);
                    }
                    $this->entityManager->remove($cart);
                    $this->entityManager->flush();
                    $i++;
                }
                $io->comment(sprintf('%d carts has been deleted which are older than 3 days with no items via loop.', $i));
            }

            try {
                $deleteCartNotAssociatedToOrder = $this->entityManager->getRepository(Cart::class)->deleteCartNotAssociatedToOrder(30)->execute();
                $io->comment(sprintf('%d carts has been deleted which are older than 30 days with no associated orders.', $deleteCartNotAssociatedToOrder));
            } catch (\Exception $e) {
                $deleteCartNotAssociatedToOrder = $this->entityManager->getRepository(Cart::class)->deleteCartNotAssociatedToOrder(30, false)->getResult();
                $i = 0;
                /** @var Cart $cart */
                foreach ($deleteCartNotAssociatedToOrder as $cart) {
                    $io->comment(sprintf('%d: Cart %d has been deleted.', $i, $cart->getId()));
                    foreach ($cart->getCartItems() as $item) {
                        $this->entityManager->remove($item);
                    }
                    $this->entityManager->remove($cart);
                    $this->entityManager->flush();
                    $i++;
                }
                $io->comment(sprintf('%d carts has been deleted which are older than 30 days with no associated orders via loop.', $i));
            }
        } finally {
            $lock->release();
        }


        return Command::SUCCESS;
    }
}