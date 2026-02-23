<?php 

namespace App\Service;

use App\Entity\Admin\Faq\Faq;
use App\Entity\Admin\Faq\FaqType;
use App\Repository\FaqTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FaqTypeRepository $faqTypeRepo
    ) {}

    public function importFaqs(UploadedFile $file, ?callable $staticCallback = null): array
    {
        $rows = $this->parseFile($file);

        $existingTypes = $this->faqTypeRepo->findAll();
        $faqTypesMap = [];
        foreach ($existingTypes as $type) {
            $faqTypesMap[strtolower($type->getName())] = $type;
        }

        $maxSortOrder = $this->faqTypeRepo->getMaxSortOrder() - 1;

        $imported = [];
        foreach ($rows as $row) {
            $typeName = trim($row['A'] ?? '');
            $question = trim($row['B'] ?? '');
            $answer = trim($row['C'] ?? '');
            $showOnEditor = filter_var($row['D'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $keywords = array_filter(array_map('trim', explode(',', $row['E'] ?? '')));

            if ($typeName === '' || $question === '' || $answer === '') continue;

            $typeKey = strtolower($typeName);

            if (isset($faqTypesMap[$typeKey])) {
                $faqType = $faqTypesMap[$typeKey];
            } else {
                $faqType = new FaqType();
                $faqType->setName($typeName)
                        ->setIsEnabled(true)
                        ->setSortOrder(++$maxSortOrder);

                $this->em->persist($faqType);
                $faqTypesMap[$typeKey] = $faqType;
            }

            $existingFaq = $this->em->getRepository(Faq::class)
                ->findOneBy(['question' => $question, 'type' => $faqType]);

            if ($existingFaq) {
                $existingFaq->setAnswer($answer)
                            ->setShowOnEditor($showOnEditor)
                            ->setKeywords($keywords);
            } else {
                $faq = new Faq();
                $faq->setType($faqType)
                    ->setQuestion($question)
                    ->setAnswer($answer)
                    ->setShowOnEditor($showOnEditor)
                    ->setKeywords($keywords);
                $this->em->persist($faq);
            }

            $imported[] = $question;
        }

        $this->em->flush();

        if ($staticCallback) {
            $staticCallback($imported);
        }

        return $imported;
    }

    private function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if ($ext === 'csv') {
            $fileContent = file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $delimiters = [',', ';', '|', "\t"];
            $parsed = false;

            foreach ($delimiters as $delimiter) {
                $csv = array_map(fn($line) => str_getcsv($line, $delimiter), $fileContent);

                $columnCount = array_map(fn($row) => count($row), $csv);
                if (max($columnCount) > 1) {
                    $parsed = $csv;
                    break;
                }
            }

            if (!$parsed) {
                throw new \Exception('Unable to detect CSV delimiter.');
            }

            foreach ($parsed as $index => $row) {
                if ($index === 0) continue; 
                $rows[] = [
                    'A' => $row[0] ?? '',
                    'B' => $row[1] ?? '',
                    'C' => $row[2] ?? '',
                    'D' => $row[3] ?? '',
                    'E' => $row[4] ?? '',
                ];
            }
        } else {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = $sheet->toArray(null, true, true, true);
            $rows = array_slice($allRows, 1); 
        }

        return $rows;
    }

}
