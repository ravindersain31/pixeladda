<?php

namespace App\Service;

use App\Entity\Order;
use App\Twig\AppExtension;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    public function __construct(private readonly AppExtension $appExtension){

    }

    public function exportSubscribers(array $subscribers, string $fileName): void
    {
        ob_start();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Sr.',
            'Name',
            'Email',
            'Phone',
            'Store',
            'Offers',
            'Mobile Alert',
            'Marketing',
            'Created At'
        ];
        $sheet->fromArray([$header], null, 'A1');

        $rowIndex = 2;
        foreach ($subscribers as $key => $subscriber) {
            $sheet->setCellValue('A' . $rowIndex, $key + 1);
            $sheet->setCellValue('B' . $rowIndex, $subscriber->getName() ?: '-');
            $sheet->setCellValue('C' . $rowIndex, $subscriber->getEmail() ?: '-');
            $sheet->setCellValue('D' . $rowIndex, $subscriber->getPhone() ?: '-');
            $sheet->setCellValue('E' . $rowIndex, $subscriber->getStore() ? $subscriber->getStore()->getName() : '-');
            $sheet->setCellValue('F' . $rowIndex, $subscriber->getOffers() ? 'YES' : 'NO');
            $sheet->setCellValue('G' . $rowIndex, $subscriber->getMobileAlert() ? 'YES' : 'NO');
            $sheet->setCellValue('H' . $rowIndex, $subscriber->getMarketing() ? 'YES' : 'NO');
            $sheet->setCellValue('I' . $rowIndex, $subscriber->getCreatedAt()->format('Y-m-d H:i:s'));
            $rowIndex++;
        }

        $writer = new Csv($spreadsheet);
        $writer->save('php://output');

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        exit();
    }

    public function exportSubscribersStreamed(\Generator $subscribers, string $fileName): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Sr.',
            'Name',
            'Email',
            'Phone',
            'Store',
            'Offers',
            'Mobile Alert',
            'Marketing',
            'Created At',
        ]);

        $i = 1;
        foreach ($subscribers as $subscriber) {
            fputcsv($output, [
                $i++,
                $subscriber->getName() ?: '-',
                $subscriber->getEmail() ?: '-',
                $subscriber->getPhone() ?: '-',
                $subscriber->getStore() ? $subscriber->getStore()->getName() : '-',
                $subscriber->getOffers() ? 'YES' : 'NO',
                $subscriber->getMobileAlert() ? 'YES' : 'NO',
                $subscriber->getMarketing() ? 'YES' : 'NO',
                $subscriber->getCreatedAt()?->format('Y-m-d H:i:s') ?? '-',
            ]);
        }

        fclose($output);
        exit();
    }

    public function exportOrder(Order $order, $fileName): void
    {
        ob_start();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Sr.',
            'Name',
            'SKU',
            'Size',
            'AddOns',
            'Qty',
            'Price',
            'Subtotal',
        ];
        $sheet->fromArray([$header], null, 'A1');

        $rowIndex = 2;
        foreach ($order->getOrderItems() as $key => $item) {
            $sheet->setCellValue('A' . $rowIndex, $key + 1);
            $sheet->setCellValue('B' . $rowIndex, $item->getProduct()->getParent()->getName() ?: '-');
            $sheet->setCellValue('C' . $rowIndex, $item->getProduct()->getSku() ?: '-');
            if ($item->getMetaDatakey('isCustomSize')) {
                $sheet->setCellValue('D' . $rowIndex, $item->getMetaDataKey('customSize')['templateSize']['width'] . 'x' . $item->getMetaDataKey('customSize')['templateSize']['height'] ?: '-');
            }else{
                $sheet->setCellValue('D' . $rowIndex, $item->getProduct()->getName() ?: '-');
            }
            $sheet->setCellValue('E' . $rowIndex, implode(' | ', $this->appExtension->labelForAddons($item->getAddOns())) ?: '-');
            $sheet->setCellValue('F' . $rowIndex, $item->getQuantity() ?: '-');
            $sheet->setCellValue('G' . $rowIndex, $item->getPrice() ?: '-');
            $sheet->setCellValue('H' . $rowIndex, $item->getPrice() * $item->getQuantity() ?: '-');
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        header('Content-Type: application/xlsx');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        exit();
    }

    public function exportUsersWithAddress(array $orders, string $fileName): void
    {
        ob_start();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Sr.',
            'First Name',
            'Last Name',
            'Email',
            'Phone Number',
            'Country',
            'state',
            'ZipCode',
            'Billing First Name',
            'Billing Last Name',
            'Billing Email',
            'Billing Phone Number',
            'Billing Country',
            'Billing State',
            'Billing ZipCode',
        ];

        $sheet->fromArray([$header], null, 'A1');

        $rowIndex = 2;
        foreach ($orders as $key => $order) {
            if(empty($order->getShippingAddress()) && empty($order->getBillingAddress())) {
                continue;
            }
            $sheet->setCellValue('A' . $rowIndex, $key + 1);
            $sheet->setCellValue('B' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['firstName'] : '-');
            $sheet->setCellValue('C' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['lastName'] : '-');
            $sheet->setCellValue('D' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['email'] : '-');
            $sheet->setCellValue('E' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['phone'] : '-');
            $sheet->setCellValue('F' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['country'] : '-');
            $sheet->setCellValue('G' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['state'] : '-');
            $sheet->setCellValue('H' . $rowIndex, !empty($order->getShippingAddress()) ? $order->getShippingAddress()['zipcode'] : '-');

            $sheet->setCellValue('I' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['firstName'] : '-');
            $sheet->setCellValue('J' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['lastName'] : '-');
            $sheet->setCellValue('K' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['email'] : '-');
            $sheet->setCellValue('L' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['phone'] : '-');
            $sheet->setCellValue('M' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['country'] : '-');
            $sheet->setCellValue('N' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['state'] : '-');
            $sheet->setCellValue('O' . $rowIndex, !empty($order->getBillingAddress()) ? $order->getBillingAddress()['zipcode'] : '-');
            $rowIndex++;
        }
        $writer = new Csv($spreadsheet);
        $writer->save('php://output');

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        exit();
    }

    public function exportFaqs(array $staticFaqs = [], array $dynamicFaqs = [], string $fileName = 'faqs.xlsx'): void
    {
        ob_start();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([[
            'Type',
            'Question',
            'Answer',
            'Show On Editor',
            'Keywords'
        ]], null, 'A1');

        $row = 2;

        if (!empty($staticFaqs)) {
            foreach ($staticFaqs as $type => $faqs) {
                foreach ($faqs as $question => $answer) {
                    $sheet->setCellValue("A{$row}", $type);
                    $sheet->setCellValue("B{$row}", $question);
                    $sheet->setCellValue("C{$row}", $answer);
                    $sheet->setCellValue("D{$row}", 'true');
                    $sheet->setCellValue("E{$row}", '');
                    $row++;
                }
            }
        } elseif (!empty($dynamicFaqs)) {
            foreach ($dynamicFaqs as $type => $faqObjects) {
                foreach ($faqObjects as $faq) {
                    $sheet->setCellValue("A{$row}", $type);
                    $sheet->setCellValue("B{$row}", $faq->getQuestion());
                    $sheet->setCellValue("C{$row}", $faq->getAnswer());
                    $sheet->setCellValue("D{$row}", $faq->isShowOnEditor() ? 'true' : 'false');

                    $keywords = $faq->getKeywords();
                    $keywordsString = is_array($keywords) ? implode(", ", $keywords) : (string)$keywords;
                    $sheet->setCellValue("E{$row}", $keywordsString);

                    $row++;
                }
            }
        } else {
            throw new \Exception("No FAQs provided to export.");
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');

        $writer->save('php://output');
        exit();
    }

}
