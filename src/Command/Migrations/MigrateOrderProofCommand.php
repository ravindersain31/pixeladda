<?php

namespace App\Command\Migrations;

use App\Entity\AdminUser;
use App\Entity\Vich\EmbeddedFile;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\UserFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'migrations:migrate:order:proof',
    description: 'Add a short description for your command',
)]
class MigrateOrderProofCommand extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
        $this->sourceConnection = DriverManager::getConnection(['url' => $_ENV['DATABASE1_URL']]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orders = $this->entityManager->getRepository(Order::class)->findBy(['version' => 'V1', 'orderId' => '8800954589']);

        $byUser = $this->entityManager->getReference(AdminUser::class, 4);

        $index = 1;
        /** @var Order $order */
        foreach ($orders as $order) {
            $orderUser = $order->getUser();
            $io->comment('#' . $index . ' Working for Order Id: ' . $order->getOrderId());
//            foreach ($order->getOrderMessages() as $message) {
//                $message->setOrder(null);
//                $this->entityManager->persist($message);
//            }
//            $this->entityManager->flush();

            $migratedData = $order->getMetaDataKey('migratedData');
            $data = $this->getData(intval($migratedData['order_id']));
            foreach ($data['proofs'] as $proof) {

                $files = explode(',', $proof['proof']);
                $imageName = '';
                $pdfName = '';
                foreach ($files as $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($ext === 'pdf') {
                        $pdfName = $file;
                    } else {
                        $imageName = $file;
                    }
                }

                $image = $this->createUserFile($imageName, $byUser, 'PROOF_IMAGE');;
                $pdf = $this->createUserFile($pdfName, $byUser, 'PROOF_FILE');

                $proofMessage = new OrderMessage();
                $proofMessage->setOrder($order);
                $proofMessage->setContent(nl2br($proof['proof_comment']));
                $proofMessage->setType('PROOF');
                $proofMessage->setSentBy($byUser);
                $proofMessage->setSentAt(new \DateTimeImmutable($proof['created_at']));
                $proofMessage->addFile($image);
                $proofMessage->addFile($pdf);

                $this->entityManager->persist($proofMessage);
                $this->entityManager->flush();

                if ($proof['is_approved'] !== 1 && strlen($proof['customer_comment']) > 4) {
                    $changesMessage = new OrderMessage();
                    $changesMessage->setOrder($order);
                    $changesMessage->setContent(nl2br($proof['customer_comment']));
                    $changesMessage->setType('CHANGES_REQUESTED');
                    $changesMessage->setSentBy($orderUser);
                    $changesMessage->setSentAt(new \DateTimeImmutable($proof['customer_updated_at']));

                    if (!empty($proof['customer_upload'])) {
                        $customerUploads = explode(',', $proof['customer_upload']);
                        foreach ($customerUploads as $upload) {
                            $customerFile = $this->createUserFile($upload, $orderUser, 'CHANGES_REQUESTED');;
                            $changesMessage->addFile($customerFile);
                        }
                    }

                    $this->entityManager->persist($changesMessage);
                    $this->entityManager->flush();
                } else {
                    $order->setProofApprovedAt(new \DateTimeImmutable($proof['customer_updated_at']));
                    $order->setApprovedProof($proofMessage);
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();
                }
            }

            $index++;
        }
        $io->success('Migration Completed');

        return Command::SUCCESS;
    }

    private function createUserFile($fileName, $byUser, $type = 'GENERAL'): UserFile
    {
        $file = new UserFile();
        $file->setVersion('V1');
        $pdfFile = new EmbeddedFile();
        $pdfFile->setName($fileName);
        $pdfFile->setOriginalName($fileName);
        $file->setFile($pdfFile);
        $file->setType($type);
        $file->setUploadedBy($byUser);
        return $file;
    }

    private function getData(int $orderId): array
    {
        return [
            'proofs' => $this->sourceConnection->fetchAllAssociative("SELECT * FROM sm3_order_product_proof AS O WHERE O.order_id = " . $orderId),
        ];
    }

}
