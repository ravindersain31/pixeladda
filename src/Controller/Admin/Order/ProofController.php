<?php

namespace App\Controller\Admin\Order;

use App\Entity\AdminFile;
use App\Entity\Order;
use App\Entity\OrderMessage;
use App\Entity\User;
use App\Entity\UserFile;
use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Event\OrderProofUploadedEvent;
use App\Form\Admin\Order\ProofType;
use App\Form\Admin\Order\UploadPrintCutFileType;
use App\Helper\VichS3Helper;
use App\Repository\OrderRepository;
use App\Service\OrderLogger;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProofController extends AbstractController
{

    use StoreTrait;

    #[Route('/orders/{orderId}/proofs', name: 'order_proofs')]
    public function transactions(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $messages = $entityManager->getRepository(OrderMessage::class)->getProofMessages($order);


        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
            'messages' => $messages,
        ]);
    }

    #[Route('/orders/{orderId}/proof/upload', name: 'order_proof_upload')]
    public function uploadProof(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, OrderLogger $orderLogger, VichS3Helper $s3Helper): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        $needProof = $order->isNeedProof();

        if($order->getStatus() === OrderStatusEnum::PROOF_APPROVED && $needProof) {
            $this->addFlash('warning', 'You can\'t upload proof as the proof has already been <b>APPROVED</b> for this order.');
            return $this->redirectToRoute('admin_order_proofs', ['orderId' => $orderId]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProofType::class, null, [
            'order' => $order,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $orderLogger->setOrder($order);

            $proof = $form->getData();

            $image = new UserFile();
            $image->setFileObject($proof['proofImage']);
            $image->setType('PROOF_IMAGE');
            $image->setUploadedBy($user);
            $entityManager->persist($image);

            $pdf = new UserFile();
            $pdf->setFileObject($proof['proofFile']);
            $pdf->setType('PROOF_FILE');
            $pdf->setUploadedBy($user);
            $entityManager->persist($pdf);

            $entityManager->flush();

            $message = new OrderMessage();
            $message->setOrder($order);
            $message->setType('PROOF');
            $message->setSentBy($user);
            $message->setContent(nl2br($proof['content']));
            $message->addFile($image);
            $message->setIsBlank($proof['isBlank'] ?? false);
            $message->addFile($pdf);

            $entityManager->persist($message);

            $isProofChanged = false;

            if($needProof){
                if (!$order->getIsApproved()) {
                    $order->setStatus(OrderStatusEnum::PROOF_UPLOADED);
                    $order->setProofDesigner(null);
                } else {
                    $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                    if ($order->getApprovedProof() instanceof OrderMessage) {
                        $order->setApprovedProof($message);
                        $order->setProofApprovedAt(new \DateTimeImmutable());
                        $orderLogger->log('Last approved proof has been removed by as new proof has been uploaded');
                        $isProofChanged = true;
                    }
                }
            } else {
                $order->setProofApprovedAt(new \DateTimeImmutable());
                $order->setApprovedProof($message);
                $order->setStatus(OrderStatusEnum::PROOF_APPROVED);
                $order->setIsApproved(true);
            }

            $entityManager->persist($order);

            $entityManager->flush();

            if ($needProof) {
                $this->eventDispatcher->dispatch(new OrderProofUploadedEvent($order, $message, $isProofChanged), OrderProofUploadedEvent::NAME);
            }

            $this->addFlash('success', 'Proof has been uploaded successfully');
            $content = 'A new proof has been uploaded by ' . $user->getUsername() . '
                <br/>
                <b>Comments:</b> ' . $message->getContent() . '
                <br/> 
                <a href="' . $s3Helper->asset($image, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof Image</a>
                <a href="' . $s3Helper->asset($pdf, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof File</a>';
            $orderLogger->log($content);
            if ($order->getIsApproved()) {
                $this->proofApprovedLog($order, $message, $orderLogger, $s3Helper);
            }
        } else {
            $errors = $form->getErrors(true);
            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }

            $this->addFlash('warning', 'There was some issues in uploading Proof. Please try again later.');
        }
        return $this->redirectToRoute('admin_order_proofs', ['orderId' => $orderId]);
    }

    #[Route('/orders/{orderId}/print-cut-file', name: 'order_upload_print_cut_file')]
    public function uploadPrintCutFile(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, OrderLogger $orderLogger, VichS3Helper $s3Helper): Response
    {
        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UploadPrintCutFileType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $proof = $form->getData();
            if (!$form->getData()['printFile'] && !$form->getData()['cutFile'] && !$form->getData()['vectorFile']) {
                $this->addFlash('warning', 'You must upload either Print File or Cut File or Vector File.');
                return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
            }

            $printFileFile = $form->getData()['printFile'];
            $cutFileFile = $form->getData()['cutFile'];
            $vectorFileFile = $form->getData()['vectorFile'];

            $printFile = null;
            $cutFile = null;
            $vectorFile = null;

            if ($printFileFile) {
                $printFile = $this->handleFileUpload($printFileFile, AdminFile::FILE_TYPE['PRINT_FILE'], $user, $entityManager);
            }

            if ($cutFileFile) {
                $cutFile = $this->handleFileUpload($cutFileFile, AdminFile::FILE_TYPE['CUT_FILE'], $user, $entityManager);
            }

            if ($vectorFileFile) {
                $vectorFile = $this->handleFileUpload($vectorFileFile, AdminFile::FILE_TYPE['VECTOR_FILE'], $user, $entityManager);
            }

            $message = new OrderMessage();
            $message->setOrder($order);
            $message->setType('PRINT_CUT_FILE');
            $message->setSentBy($user);
            $messageContent = '';

            if (isset($printFile)) {
                $messageContent .= '<b>Print </b>';
            }

            if (isset($cutFile)) {
                $messageContent .= '<b>Cut </b>';
            }

            if (isset($vectorFile)) {
                $messageContent .= '<b>Vector </b>';
            }

            $message->setContent(nl2br($messageContent . ' <b>File</b> uploaded by ' . $user->getUsername()));
            if (isset($printFile)) {
                $message->addAdminFile($printFile);
            }
            if (isset($cutFile)) {
                $message->addAdminFile($cutFile);
            }

            if (isset($vectorFile)) {
                $message->addAdminFile($vectorFile);
            }

            $entityManager->persist($message);
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Print / Cut File has been uploaded successfully');
            $this->logOrderUpload($orderLogger, $user, $message, $printFile, $cutFile, $vectorFile, $s3Helper);
        } else {
            $this->addFlash('warning', 'There was some issues in uploading Files. Please try again later.');
        }
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
    }

    private function handleFileUpload($file, $type, $user, $entityManager): AdminFile
    {
        $adminFile = new AdminFile();
        $adminFile->setFileObject($file);
        $adminFile->setType($type);
        $adminFile->setUploadedBy($user);
        $entityManager->persist($adminFile);

        return $adminFile;
    }

    private function logOrderUpload($orderLogger, $user, $message, $printFile, $cutFile, $vectorFile, $s3Helper): void
    {

        $content = 'A new';
        $content .= isset($printFile) ? ' Print' : '';
        $content .= isset($cutFile) ? ' Cut' : '';
        $content .= isset($vectorFile) ? ' Vector' : '';
        $content .= ' File has been uploaded by ' . $user->getUsername() . '<br/>'
            . '<b>Comments:</b> ' . $message->getContent() . '<br/>';

        if (isset($printFile)) {
            $content .= '<a href="' . $s3Helper->asset($printFile, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Print File</a>';
        }

        if (isset($cutFile)) {
            $content .= '<a href="' . $s3Helper->asset($cutFile, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Cut File</a>';
        }

        if (isset($vectorFile)) {
            $content .= '<a href="' . $s3Helper->asset($vectorFile, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Vector File</a>';
        }


        $orderLogger->setOrder($message->getOrder());
        $orderLogger->log($content);
    }

    private function proofApprovedLog(Order $order, OrderMessage $approvedProof, OrderLogger $orderLogger, VichS3Helper $s3Helper): void
    {
        $orderLogger->setOrder($order);
        try {
            $image = '';
            $pdf = '';
            $user = $order->getUser();
            $userName = $user->getName() . ' <small>(' . $user->getUsername() . ')</small>';

            foreach ($approvedProof->getFiles() as $file) {
                if ($file->getType() == 'PROOF_IMAGE') {
                    $image = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof Image</a>';
                } elseif ($file->getType() == 'PROOF_FILE') {
                    $pdf = '<a href="' . $s3Helper->asset($file, 'fileObject') . '" class="btn btn-link p-0" target="_blank">View Proof File</a>';
                }
            }
            $content = '<b>Proof Approved By:</b> ' . $userName . '
                        <br/>
                        <b>Payment Method:</b> ' . $order->getPaymentMethod() . '
                        <br/>
                        <b>Proof Approved At:</b> ' . (new \DateTimeImmutable())->format('M d, Y h:i:s A') . '
                        <br/>
                        <b>Comments:</b> ' . $approvedProof->getContent() . '
                        <br/>
                        ' . $image . ' | ' . $pdf . '';

            $orderLogger->log($content);
        } catch (\Exception $e) {
            $orderLogger->log($e->getMessage());
        }
    }

    #[Route('/orders/{orderId}/move-to-upload-proof', name: 'order_move_to_upload_proof')]
    public function moveToUploadProof(string $orderId, OrderRepository $repository, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, OrderLogger $orderLogger, VichS3Helper $s3Helper): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $order = $repository->findByOrderId($orderId);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('Order not found');
        }

        if (in_array($order->getStatus(), [OrderStatusEnum::PROOF_UPLOADED, OrderStatusEnum::CHANGES_REQUESTED, OrderStatusEnum::RECEIVED])) {
            $this->addFlash('warning', 'You can\'t move to upload proof as the order status is <b>' . OrderStatusEnum::LABELS[$order->getStatus()] . '</b>');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $warehouseOrder = $order->getWarehouseOrder();
        if ($warehouseOrder && $warehouseOrder->getPrintStatus() !== WarehouseOrderStatusEnum::READY) {
            $this->addFlash('warning', 'You can\'t move to upload proof as the order queue status has changed to <b>' . WarehouseOrderStatusEnum::getLabel($warehouseOrder->getPrintStatus()) . '</b>');
            return $this->redirectToRoute('admin_order_overview', ['orderId' => $order->getOrderId()]);
        }

        $orderLogger->setOrder($order);
        $orderLogger->log('Order has been moved to <b>Upload Proof</b>, and before moving the order status was <b>' . OrderStatusEnum::LABELS[$order->getStatus()] . '</b>');

        $order->setStatus(OrderStatusEnum::RECEIVED);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order has been moved to upload proof');
        return $this->redirectToRoute('admin_order_overview', ['orderId' => $orderId]);
    }
}
