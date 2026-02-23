<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\EmailQuote;
use App\Entity\SavedCart;
use App\Entity\SavedDesign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

class CleanerService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LockFactory $lockFactory)
    {
    }

    public function deleteCartWithZeroTotal(): string
    {
        $lock = $this->lockFactory->createLock('cleaner:cart', ttl: 120);
        if (!$lock->acquire()) {
            return 'The request is already running in another process.';
        }

        try {
            $deleteCartWithZeroTotal = $this->entityManager->getRepository(Cart::class)->deleteCartWithZeroTotal(3)->execute();
            $message = sprintf('%d carts has been deleted which are older than 3 days with no items.', $deleteCartWithZeroTotal);
        } catch (\Exception $e) {
            $deleteCartWithZeroTotal = $this->entityManager->getRepository(Cart::class)->deleteCartWithZeroTotal(3, false)->getResult();
            $i = 0;
            /** @var Cart $cart */
            foreach ($deleteCartWithZeroTotal as $cart) {
                foreach ($cart->getCartItems() as $item) {
                    $this->entityManager->remove($item);
                }
                $this->entityManager->remove($cart);
                $this->entityManager->flush();
                $i++;
            }
            $message = sprintf('%d carts has been deleted which are older than 3 days with no items via loop.', $i);
        } finally {
            $lock->release();
            return $message;
        }
    }

    public function deleteCartNotAssociatedToOrder(): string
    {
        $lock = $this->lockFactory->createLock('cleaner:deleteCartNotAssociatedToOrder', ttl: 120);
        if (!$lock->acquire()) {
            return 'The request is already running in another process.';
        }
        try {
            $deleteCartNotAssociatedToOrder = $this->entityManager->getRepository(Cart::class)->deleteCartNotAssociatedToOrder(30)->execute();
            $message = sprintf('%d carts has been deleted which are older than 30 days with no associated orders.', $deleteCartNotAssociatedToOrder);
        } catch (\Exception $e) {
            $deleteCartNotAssociatedToOrder = $this->entityManager->getRepository(Cart::class)->deleteCartNotAssociatedToOrder(30, false)->getResult();
            $i = 0;
            /** @var Cart $cart */
            foreach ($deleteCartNotAssociatedToOrder as $cart) {
                foreach ($cart->getCartItems() as $item) {
                    $this->entityManager->remove($item);
                }
                $this->entityManager->remove($cart);
                $this->entityManager->flush();
                $i++;
            }
            $message = sprintf('%d carts has been deleted which are older than 30 days with no associated orders via loop.', $i);
        } finally {
            $lock->release();
            return $message;
        }
    }

    public function deleteExpiredEmailQuote(): string
    {
        $lock = $this->lockFactory->createLock('cleaner:deleteExpiredEmailQuote', ttl: 120);
        if (!$lock->acquire()) {
            return 'The request is already running in another process.';
        }
        $message = 'failed';
        try {
            $deletedExpired = $this->entityManager->getRepository(EmailQuote::class)->deleteExpired()->execute();
            $message = sprintf('%d email quote has been deleted which are expired.', $deletedExpired);
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $lock->release();
            return $message;
        }
    }

    public function deleteExpiredSavedCart(): string
    {
        $lock = $this->lockFactory->createLock('cleaner:deleteExpiredSavedCart', ttl: 120);
        if (!$lock->acquire()) {
            return 'The request is already running in another process.';
        }
        $message = 'failed';
        try {
            $deletedExpired = $this->entityManager->getRepository(SavedCart::class)->deleteExpired()->execute();
            $message = sprintf('%d saved carts has been deleted which are expired.', $deletedExpired);
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $lock->release();
            return $message;
        }
    }

    public function deleteExpiredSavedDesign(): string
    {
        $lock = $this->lockFactory->createLock('cleaner:deleteExpiredSavedDesign', ttl: 120);
        if (!$lock->acquire()) {
            return 'The request is already running in another process.';
        }
        $message = 'failed';
        try {
            $deletedExpired = $this->entityManager->getRepository(SavedDesign::class)->deleteExpired()->execute();
            $message = sprintf('%d saved design has been deleted which are expired.', $deletedExpired);
        } catch (\Exception $e) {
            return $e->getMessage();
        } finally {
            $lock->release();
            return $message;
        }
    }

}