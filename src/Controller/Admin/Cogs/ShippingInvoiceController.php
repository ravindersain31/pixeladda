<?php

namespace App\Controller\Admin\Cogs;

use App\Entity\Admin\Cogs\ShippingInvoiceFile;
use App\Entity\Order;
use App\Entity\Admin\ShippingInvoice;
use App\Repository\Admin\Cogs\ShippingInvoiceFileRepository;
use App\Repository\Admin\ShippingInvoiceRepository;
use App\Repository\OrderRepository;
use App\Repository\OrderShipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/cogs/shipping', name: 'cogs_shipping_')]
class ShippingInvoiceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository
    ) {
    }

    #[Route('/invoice-upload', name: 'invoice_upload', methods: ['POST'])]
    public function uploadCSV(Request $request): JsonResponse
    {

        $uploadedFile = $request->files->get('file');
        $carrier = $request->get('carrier');
        $records = json_decode($request->get('records'), true) ?? [];

        $allowedCarriers = ['UPS', 'FedEx'];
        if (!in_array($carrier, $allowedCarriers, true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid carrier provided.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$uploadedFile || empty($records)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid request: File or records missing.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $requiredColumns = $this->getRequiredColumns($carrier);
        $errors = [];

        foreach ($records as $row) {
            foreach ($requiredColumns as $column) {
                if (!array_key_exists($column, $row)) {
                    return $this->json([
                        'status' => 'error',
                        'message' => "Missing required column: $column",
                        'errors' => [$row],
                    ]);
                }
            }
        }

        $shippingInvoiceFile = $this->entityManager->getRepository(ShippingInvoiceFile::class)->findOneBy(['originalName' => $uploadedFile->getClientOriginalName()]);

        if ($shippingInvoiceFile) {
            $shippingInvoiceFile->setUploadedBy($this->getUser() ?? null);
            $shippingInvoiceFile->setUpdatedAt(new \DateTimeImmutable());
            $shippingInvoiceFile->setCarrier($carrier);
        } else {
            $shippingInvoiceFile = new ShippingInvoiceFile();
            $shippingInvoiceFile->setFileObject($uploadedFile);
            $shippingInvoiceFile->setOriginalName($uploadedFile->getClientOriginalName());
            $shippingInvoiceFile->setStatus('uploaded');
            $shippingInvoiceFile->setUploadedBy($this->getUser() ?? null);
            $shippingInvoiceFile->setCarrier($carrier);
            $this->entityManager->persist($shippingInvoiceFile);
        }

        $header = array_keys($records[0]);

        $count = 0;

        foreach ($records as $row) {
            $orderId = $carrier === 'FedEx'
                ? ($row['Original Customer Reference'] ?? null)
                : ($row['Reference No.1'] ?? null);

            $order = $this->getOrder($orderId);

            if ($order) {
                $this->saveInvoice($header, $carrier, $row, $order, $shippingInvoiceFile);
                $count++;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        return $this->json([
            'status' => empty($errors) ? 'success' : 'partial_success',
            'message' => empty($errors) ? 'CSV and file uploaded successfully.' : 'Some records failed to upload.',
            'errors' => $errors
        ]);
    }

    private function getRequiredColumns(string $carrier): array
    {
        return match ($carrier) {
            'FedEx' => [
                'Invoice Number',
                'Invoice Date',
                'Net Charge Amount',
                'Express or Ground Tracking ID',
                'Original Customer Reference',
            ],
            'UPS' => [
                'Reference No.1',
                'Reference No.2',
                'Reference No.3',
                'Tracking Number',
                'Invoice Number',
                'Invoice Section',
                'Billed Charge',
                'Invoice Date',
            ],
            default => [],
        };
    }

    #[Route('/update-carrier-invoices', name: 'update_carrier_invoices', methods: ['GET'])]
    public function updateCarrierInvoices(): JsonResponse
    {
        $invoicefiles = $this->entityManager->getRepository(ShippingInvoiceFile::class)->findAll();

        foreach ($invoicefiles as $file) {
            if (str_contains(strtolower($file->getOriginalName()), '.csv')) {
                $file->setCarrier('UPS');
            } elseif (str_contains(strtolower($file->getOriginalName()), '.xlsx') || str_contains(strtolower($file->getOriginalName()),'.xls')) {
                $file->setCarrier('FedEx');
            }
            $this->entityManager->persist($file);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Carrier updated successfully.',
        ]);
    }

    private function saveInvoice($header, string $carrier, array $row, Order $order, ?ShippingInvoiceFile $file = null): void
    {
        $trackingNumber = $carrier === 'FedEx'
            ? $row['Express or Ground Tracking ID'] ?? null
            : $row['Tracking Number'] ?? null;

        $invoiceSection = $row['Invoice Section'] ?? 'default';

        $invoice = $this->entityManager->getRepository(ShippingInvoice::class)->findOneBy([
            'order' => $order,
            'trackingNumber' => $trackingNumber,
            'invoiceSection' => $invoiceSection
        ]) ?? new ShippingInvoice();

        $invoiceType = match ($carrier) {
            'FedEx' => $this->determineFedExInvoiceType($row),
            default => $this->determineOutboundAdjustment($header, $row),
        };

        $invoice
            ->setOrder($order)
            ->setInvoiceNumber($row['Invoice Number'] ?? '')
            ->setTrackingNumber($trackingNumber)
            ->setInvoiceType($invoiceType)
            ->setInvoiceSection($invoiceSection)
            ->setReferenceNumbers([
                'ref1' => $row['Original Customer Reference'] ?? $row['Reference No.1'] ?? '',
                'ref2' => $row['Original Ref#2'] ?? $row['Reference No.2'] ?? '',
                'ref3' => $row['Original Ref#3/PO Number'] ?? $row['Reference No.3'] ?? '',
            ])
            ->setBilledCharge((float) ($row['Net Charge Amount'] ?? $row['Billed Charge'] ?? 0));

        if ($file) {
            $invoice->setFile($file);
            $file->setGeneratedAt($this->getDateValue($header, $row, 'Invoice Date'));
            $this->entityManager->persist($file);
        }

        $this->entityManager->persist($invoice);
    }

    private function determineFedExInvoiceType(array $row): string
    {
        $baseCharge = (float) ($row['Transportation Charge Amount'] ?? 0);
        $adjustments = (float) ($row['Tracking ID Charge Amount'] ?? 0);

        if ($baseCharge && $adjustments) {
            return ShippingInvoice::INVOICE_TYPE_TOTAL;
        }
        if ($baseCharge) {
            return ShippingInvoice::INVOICE_TYPE_OUTBOUND;
        }
        if ($adjustments) {
            return ShippingInvoice::INVOICE_TYPE_ADJUSTMENT;
        }

        return ShippingInvoice::INVOICE_TYPE_OUTBOUND;
    }

    private function getOrder(string|int $orderId): ?Order
    {
        return $this->orderRepository->getOrder(orderId: $orderId);
    }

    private function determineOutboundAdjustment(array $header, array $row): string
    {
        $invoiceType = $this->getColumnValue($header, $row, 'Invoice Section');
        return str_contains(strtolower($invoiceType), 'adjustments') ? ShippingInvoice::INVOICE_TYPE_ADJUSTMENT : ShippingInvoice::INVOICE_TYPE_OUTBOUND;
    }

    private function getColumnValue(array $header, array $row, string $column, mixed $default = null): mixed
    {
        // Get the value from the row using the column name from the header
        $index = array_search($column, $header);
        return $index !== false ? ($row[$column] ?? $default) : $default;
    }

    private function getDateValue(array $header, array $row, string $column): ?\DateTimeImmutable
    {
        $value = $this->getColumnValue($header, $row, $column);
        return $value ? new \DateTimeImmutable($value) : new \DateTimeImmutable();
    }

    #[Route('/clear-non-linked-invoices', name: 'clear_non_linked_invoices', methods: ['GET'])]
    public function clearNonLinked(Request $request): JsonResponse
    {
        $invoiceRepository = $this->entityManager->getRepository(ShippingInvoice::class);
        $invoices = $invoiceRepository->findBy(['file' => null]);

        $errors = [];
        $count = 0;
        foreach ($invoices as $invoice) {
            $this->entityManager->remove($invoice);
            $count++;
        }

        // clear invoices files
        $fileRepository = $this->entityManager->getRepository(ShippingInvoiceFile::class);
        $files = $fileRepository->findBy(['uploadedBy' => null]);
        foreach ($files as $file) {
            $this->entityManager->remove($file);
        }

        // Final flush
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $this->json([
            'status' => 'success',
            'message' => 'Invoices cleared successfully.',
            'count' => $count
        ]);
    }

    #[Route('/delete-invoices/{fileId}', name: 'delete_invoices', methods: ['DELETE'])]
    public function deleteInvoices(int $fileId, ShippingInvoiceRepository $shippingInvoiceRepository, ShippingInvoiceFileRepository $shippingInvoiceFileRepository): JsonResponse
    {
        $invoices = $shippingInvoiceRepository->findBy(['file' => $fileId]);    
        $file = $shippingInvoiceFileRepository->find($fileId);    

        if (!$invoices && !$file) {
            return new JsonResponse(['success' => false, 'error' => 'No invoices or file found for this ID.'], 404);
        }

        try {
            foreach ($invoices as $invoice) {
                $this->entityManager->remove($invoice);
            }

            if ($file) {
                $this->entityManager->remove($file);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            return new JsonResponse(['success' => true, 'message' => 'Invoices and file deleted successfully.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'An error occurred while deleting.'], 500);
        }
    }

    private function getCsvColumnValue(array $header, array $row, string $column, mixed $default = null): mixed
    {
        $index = array_search($column, $header);
        return $index !== false && isset($row[$index]) ? $row[$index] : $default;
    }

    #[Route('/invoice-file/{invoiceFileId}/invoice-orders', name: 'invoice_orders')]
    public function invoiceOrders(int $invoiceFileId, ShippingInvoiceFileRepository $shippingInvoiceFileRepository, ShippingInvoiceRepository $shippingInvoiceRepository, OrderRepository $orderRepository): Response
    {
        $shippingInvoiceFile = $shippingInvoiceFileRepository->find($invoiceFileId);
        $uniqueOrders = $orderRepository->findUniqueOrdersByFile($invoiceFileId);

        $orders = array_map(fn($order) => [
            'orderId' => $order->getOrderId(),
            'orderAt' => $order->getOrderAt(), 
            'shippingCosts' => $order->getShippingCosts(),
            'materialCost' => $order->getMaterialCost(),
            'totalReceivedAmount' => $order->getTotalReceivedAmount(),
            'paymentLinkAmountReceived' => $order->getPaymentLinkAmountReceived(),
            'totalLaborCost' => $order->getTotalLaborCost(),
            'weightedAdsCost' => $order->getWeightedAdsCost(),
            'totalShippingCost' => $order->getTotalShippingCost(),
            'refundedAmount' => $order->getRefundedAmount(), 
            'profitAndLoss' => $order->getProfitAndLoss(),
            'grossMarginPercentage' => $order->getGrossMarginPercentage(),
            'grossMargin' => $order->getGrossMargin(),
            'netMarginPercentage' => $order->getNetMarginPercentage(),
            'netMargin' => $order->getNetMargin(),
        ], $uniqueOrders);

        return $this->render('admin/reports/shipping_invoice/invoice_reports.html.twig', [
            'shippingInvoiceFile' => $shippingInvoiceFile,
            'orders' => $orders
        ]);
    }

    #[Route('/invoice-file/{invoiceFileId}/export-invoice-orders', name: 'export_invoice_orders')]
    public function exportInvoiceOrders(int $invoiceFileId, ShippingInvoiceFileRepository $shippingInvoiceFileRepository, ShippingInvoiceRepository $shippingInvoiceRepository, OrderRepository $orderRepository): Response
    {
        $shippingInvoiceFile = $shippingInvoiceFileRepository->find($invoiceFileId);
        $uniqueOrders = $orderRepository->findUniqueOrdersByFile($invoiceFileId);
        $fileName = $shippingInvoiceFile->getFile()?->getOriginalName() ?? 'invoice_' . $shippingInvoiceFile->getId() . '.csv';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Order Id',
            'Order Date',
            'Order Total',
            'Material Cost',
            'Warehouse Labor',
            'Ads Cost',
            'Shipping Charges',
            'Shipping Adjustment',
            'Shipping Total',
            'Profit/Loss',
            'Gross Margin',
            'Net Margin',
        ];

        $sheet->fromArray([$header], null, 'A1');

        $totalReceivedAmount = 0;
        $totalMaterialCost = 0;
        $totalLaborCost = 0;
        $totalAdsCost = 0;
        $totalShippingCharges = 0;
        $totalShippingAdjustment = 0;
        $totalShippingCost = 0;
        $totalProfitAndLoss = 0;
        $totalGrossMargin = 0;
        $totalNetMargin = 0;
        $totalPaymentLinkAmountReceived = 0;
        $totalRefunded = 0;
        $totalPaidSales = 0;

        $rowIndex = 2;
        foreach ($uniqueOrders as $order) {
            $sheet->setCellValue('A' . $rowIndex, $order->getOrderId());
            $sheet->setCellValue('B' . $rowIndex, $order->getOrderAt()->format('M d, Y h:i A'));
            $sheet->setCellValue('C' . $rowIndex, number_format($order->getTotalReceivedAmount(), 2));
            $sheet->setCellValue('D' . $rowIndex, number_format($order->getMaterialCost()['totalMaterialCost'] ?? 0, 2));
            $sheet->setCellValue('E' . $rowIndex, number_format($order->getTotalLaborCost(), 2));
            $sheet->setCellValue('F' . $rowIndex, number_format($order->getWeightedAdsCost(), 2));
            $sheet->setCellValue('G' . $rowIndex, number_format($order->getShippingCosts()['shippingCharges'] ?? 0, 2));
            $sheet->setCellValue('H' . $rowIndex, number_format($order->getShippingCosts()['shippingAdjustment'] ?? 0, 2));
            $sheet->setCellValue('I' . $rowIndex, number_format($order->getTotalShippingCost(), 2));
            $sheet->setCellValue('J' . $rowIndex, number_format($order->getProfitAndLoss(), 2));
            $sheet->setCellValue('K' . $rowIndex, number_format($order->getGrossMargin(), 2) . "\n(" . number_format($order->getGrossMarginPercentage(), 2) . "%) ");
            $sheet->setCellValue('L' . $rowIndex, number_format($order->getNetMargin(), 2) . "\n(" . number_format($order->getNetMarginPercentage(), 2) . "%) ");
            
            $rowIndex++;

            $totalReceivedAmount = $totalReceivedAmount + $order->getTotalReceivedAmount();
            $totalMaterialCost = $totalMaterialCost + $order->getMaterialCost()['totalMaterialCost'];
            $totalLaborCost = $totalLaborCost + $order->getTotalLaborCost();
            $totalAdsCost = $totalAdsCost + $order->getWeightedAdsCost();
            $totalShippingCharges = $totalShippingCharges + $order->getShippingCosts()['shippingCharges'];
            $totalShippingAdjustment = $totalShippingAdjustment + $order->getShippingCosts()['shippingAdjustment'];
            $totalShippingCost = $totalShippingCost + $order->getTotalShippingCost();
            $totalProfitAndLoss = $totalProfitAndLoss + $order->getProfitAndLoss();
            $totalGrossMargin = $totalGrossMargin + $order->getGrossMargin();
            $totalNetMargin = $totalNetMargin + $order->getNetMargin();
            $totalPaymentLinkAmountReceived = $totalPaymentLinkAmountReceived + $order->getPaymentLinkAmountReceived();
            $totalRefunded = $totalRefunded + $order->getRefundedAmount();

        }
        $totalPaidSales = $totalReceivedAmount + $totalPaymentLinkAmountReceived;
        $totalGrossMargin = $totalPaidSales - ($totalShippingCost + $totalRefunded + $totalMaterialCost + $totalLaborCost);
        $totalNetMargin = $totalPaidSales - ($totalShippingCost + $totalRefunded + $totalMaterialCost + $totalLaborCost + $totalAdsCost);

        $sheet->setCellValue('A' . $rowIndex, '');
        $sheet->setCellValue('B' . $rowIndex, 'Total');
        $sheet->setCellValue('C' . $rowIndex, number_format($totalReceivedAmount, 2));
        $sheet->setCellValue('D' . $rowIndex, number_format($totalMaterialCost, 2));
        $sheet->setCellValue('E' . $rowIndex, number_format($totalLaborCost, 2));
        $sheet->setCellValue('F' . $rowIndex, number_format($totalAdsCost, 2));
        $sheet->setCellValue('G' . $rowIndex, number_format($totalShippingCharges, 2));
        $sheet->setCellValue('H' . $rowIndex, number_format($totalShippingAdjustment, 2));
        $sheet->setCellValue('I' . $rowIndex, number_format($totalShippingCost, 2));
        $sheet->setCellValue('J' . $rowIndex, number_format($totalProfitAndLoss, 2));

        $grossMarginPercentage = ($totalPaidSales > 0) ? ($totalGrossMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('K' . $rowIndex, number_format($totalGrossMargin, 2) . "\n(" . number_format($grossMarginPercentage, 2) . "%)");

        $netMarginPercentage = ($totalPaidSales > 0) ? ($totalNetMargin / $totalPaidSales) * 100 : 0;
        $sheet->setCellValue('L' . $rowIndex, number_format($totalNetMargin, 2) . "\n(" . number_format($netMarginPercentage, 2) . "%)");

        $writer = new Csv($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="orders_' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    #[Route('/invoice-file/{invoiceFileId}/unmatch-tracking-labels', name: 'unmatch_tracking_labels')]
    public function shippingInvoiceOrdersUnmatchLabel(
        int $invoiceFileId, 
        ShippingInvoiceFileRepository $shippingInvoiceFileRepository, 
        UploaderHelper $uploaderHelper,
        OrderShipmentRepository $orderShipmentRepository, 
    ): Response {

        $shippingInvoiceFile = $shippingInvoiceFileRepository->find($invoiceFileId);

        if (!$shippingInvoiceFile) {
            throw $this->createNotFoundException('Invoice file not found.');
        }

        $shippingInvoiceCsvUrl = $uploaderHelper->asset($shippingInvoiceFile, 'fileObject');
        $csvData = file_get_contents('https://static.yardsignplus.com' . $shippingInvoiceCsvUrl);

        if ($csvData === false) {
            throw new \Exception('Failed to fetch CSV file from S3.');
        }

        $rows = array_map(function($line) {
            return str_getcsv($line, ",", '"', "\\");
        }, explode("\n", trim($csvData)));

        if (empty($rows) || count($rows) < 2) {
            throw new \Exception('CSV file is empty or has insufficient data.');
        }

        $header = $rows[0];
        $dataRows = array_slice($rows, 1); 
        $batchSize = 200;
        $mergedData = [];

        for ($i = 0; $i < count($dataRows); $i += $batchSize) {
            $batchRows = array_slice($dataRows, $i, $batchSize);
            $trackingNumbers = [];

            foreach ($batchRows as $row) {
                $trackingNumber = $this->getCsvColumnValue($header, $row, 'Tracking Number');

                if (!empty($trackingNumber)) {
                    $trackingNumbers[] = $trackingNumber;
                }
            }

            if (!empty($trackingNumbers)) {
                $this->processUnmatchedTrackingBatch($trackingNumbers, $batchRows, $mergedData, $header, $orderShipmentRepository);
            }
        }

        $unmatchTrackingLabels = array_values($mergedData);

        return $this->render('admin/reports/shipping_invoice/invoice_reports.html.twig', [
            'shippingInvoiceFile' => $shippingInvoiceFile,
            'unmatchTrackingLabels' => $unmatchTrackingLabels
        ]);
    }

    private function processUnmatchedTrackingBatch(array $trackingNumbers, array $batchRows, array &$mergedData, array $header, OrderShipmentRepository $orderShipmentRepository): void
    {
        $existingShipments = $orderShipmentRepository->findBy(['trackingId' => $trackingNumbers]);
        $existingTrackingIds = array_map(fn($shipment) => $shipment->getTrackingId(), $existingShipments);

        foreach ($batchRows as $row) {
            $trackingNumber = $this->getCsvColumnValue($header, $row, 'Tracking Number');

            if (empty($trackingNumber) || in_array($trackingNumber, $existingTrackingIds, true)) {
                continue;
            }

            $mergedData[$trackingNumber] ??= [
                'InvoiceNumber'  => $this->getCsvColumnValue($header, $row, 'Invoice Number'),
                'InvoiceDate'    => $this->getCsvColumnValue($header, $row, 'Invoice Date'),
                'TrackingNumber' => $trackingNumber,
                'ReferenceNo1'  => $this->getCsvColumnValue($header, $row, 'Reference No.1'),
                'shippingCharges' => 0,
                'shippingAdjustment' => 0,
                'shippingTotal' => 0,
            ];

            $section = $this->getCsvColumnValue($header, $row, 'Invoice Section');
            $billedCharge = (float) $this->getCsvColumnValue($header, $row, 'Billed Charge');

            if (str_contains(strtolower($section), 'adjustments')) {
                $mergedData[$trackingNumber]['shippingAdjustment'] += $billedCharge;
            } elseif (str_contains(strtolower($section), 'inbound')) {
                $mergedData[$trackingNumber]['shippingCharges'] += $billedCharge;
            }

            $mergedData[$trackingNumber]['shippingTotal'] = 
                $mergedData[$trackingNumber]['shippingCharges'] + $mergedData[$trackingNumber]['shippingAdjustment'];
        }
    }
}
