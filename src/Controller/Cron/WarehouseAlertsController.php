<?php

namespace App\Controller\Cron;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;

class WarehouseAlertsController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route(path: '/warehouse-alerts', name: 'cron_warehouse_alerts')]
    public function index(Request $request, LockFactory $lockFactory): Response
    {
        $lock = $lockFactory->createLock('warehouse-alerts', ttl: 300);
        if (!$lock->acquire()) {
            return $this->json(['status' => 'locked']);
        }
        try {
            $timezone = new \DateTimeZone('America/Chicago');
            $date = new \DateTimeImmutable('now', $timezone);
            $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')), $timezone);
            if ($customDate !== false) {
                $date = $customDate;
            }

            $hour = (int)$date->format('H');
            $minute = (int)$date->format('i');
            $dayOfWeek = (int)$date->format('w');
            $dayOfMonth = (int)$date->format('j');

            // Purge printers every hour
            if ($minute === 0) {
                $this->purgePrinters();
            }

            if ($hour >= 10 && $hour < 18 && $hour % 2 == 0 && $minute === 0) {
                if ($hour % 4 == 0) {
                    $this->purgeAndFillInk();
                } else {
                    $this->purgePrinters();
                }
            }

            if ($hour === 18 && $minute === 30) {
                $this->endOfDayTasks();
            }

            if ($hour === 4 && $minute === 0) {
                $this->everyDay4am();
            }

            if ($dayOfWeek === 5 && $hour === 12 && $minute === 0) {
                $this->everyFriday();
                $this->deleteUSBJunk();
            }

            if ((int)$date->format('W') % 2 == 0 && $dayOfWeek == 1 && $hour === 12 && $minute === 0) {
                $this->every2WeeksOnMonday();
            }

            if ($dayOfMonth === 1 && $hour === 10 && $minute === 0) {
                $this->monthlyTasks();
            }

            if ($dayOfMonth === 1 && $date->format('n') % 3 == 0 && $hour === 10 && $minute === 0) {
                $this->every3MonthsTasks();
            }

            if ($hour === 17 && $minute === 0 && $dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $this->scrapeFlatbed();
            }

            if ($hour === 10 && $minute === 0 && $dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $this->cleanPrintersDust();
                $this->scrapeFlatbed();
                $this->nozzleCheck();
            }

            if ($dayOfWeek >= 1 && $dayOfWeek <= 6) {
                if ($hour === 7 && $minute === 0) {
                    $this->askWarehouseForFullSheetCount();
                }
                if ($hour === 9 && $minute === 0) {
                    $this->askWarehouseForFullSheetCount();
                }
                if ($hour === 12 && $minute === 0) {
                    $this->askWarehouseForFullSheetCount();
                }
                if ($hour === 14 && $minute === 0) {
                    $this->askWarehouseForFullSheetCount();
                }
                if ($hour % 2 === 0 && $minute === 0) {
                    $this->checkAndRefillPrinterInk();
                }
            }

            if (
                ($dayOfWeek >= 1 && $dayOfWeek <= 5 || $dayOfWeek === 0) &&
                ($hour >= 19 || $hour < 7) &&
                $minute === 0
            ) {
                $this->requestNightShiftPhoto();
            }

        } finally {
            $lock->release();
        }

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d H:i:s')]);
    }

    private function askWarehouseForFullSheetCount(): void
    {
        $message = "CNC1: Please provide the count of full sheets to cut and the order numbers. Check if any updates are required with Designers.";
    }

    private function purgeAndFillInk(): void
    {
        $message = "Purge P1, P2, P3, P4, P5, P6 | Fill ink P2";
    }

    private function deleteUSBJunk(): void
    {
        $message = "Delete all junk files from each computer and USB";
    }

    private function purgePrinters(): void
    {
        $message = "Purge P1, P2, P3, P4, P5, P6";
    }

    private function endOfDayTasks(): void
    {
        // End of Day: P1 and P2
        $messageP1P2 = "End of Day: Purge, wipe P1 and P2 printheads with cleaning solution after purging, do nozzle check. Share photo. If nozzle status is good, cover the carriages before leaving.";

        // Instructions if nozzle status is bad for P1 and P2
        $messageP1P2Bad = "End of Day: P1, P2: If nozzle status is not good after multiple purges, inject cleaning solution into tubes, flush out by purging, wipe print heads with cleaning solution, and see if nozzle status resolves. If still bad, contact manufacturer immediately.";

        // End of Day: P3 and P4
        $messageP3P4 = "End of Day: Purge, wipe P3 and P4 print-heads with cleaning solution after purging, do nozzle check. Share photo. If nozzle status is good, cover the carriages before leaving.";

        // Instructions if nozzle status is bad for P3 and P4
        $messageP3P4Bad = "End of Day: P3, P4: If nozzle status is not good after multiple purges, contact manufacturer immediately.";

        $cleanUvLamp = "Clean UV Lamp on P1, P2, P3, P4, P5, P6";
    }

    private function everyDay4am(): void
    {
        $message = "Nozzle Check P1, P2, P3, P4, P5, P6. Share Photo for each.";
    }

    public function everyFriday(): void
    {
        $messages = [
            $this->getConsolidatedInventoryMessage()
        ];
        foreach ($messages as $message) {
        }
    }

    private function getConsolidatedInventoryMessage()
    {
        return implode("\n", [
            "Clean A/C Filters",
            "Clean CNC Pumps",
            "Clean Anti static bars P3, P4, P5, P6 (alcohol & wipe gray holes)",
            "Drain ink P1, P2, P3, P4, P5, P6",
            "Check Full Sheets",
            "Check 24x18 Precut Sheets",
            "Check 18x12 Precut Sheets",
            "Check 18x24 Precut Sheets",
            "Check 24x24 Precut Sheets",
            "Check Printer Ink Inventory (P1, P2, P3, P4, P5, P6)",
            "Check CNC Blades & Bristles (Each Size)",
            "Check Supplies Inventory (Printer Paper, Staples, Box Cutters, Proof Printer Ink, Labels Printer Rolls, Water Cases)",
            "Check Paper Rolls, Tape Inventory",
            "Count Remaining Sheets (Full, 18x12, 24x18, 18x24, 24x24)",
            "Check Boxes (18x12x8, 18x12x16, 24x18x6, 24x18x12, 24x18x18, 24x24x8, 24x24x16, 30x24x6, 30x24x10, 30x20x6, 30x20x12, 36x24x6, 36x24x12, 36x24x18, 48x24x8, Kraft Rolls)",
            "Count Remaining Stakes (50 qty, 100qty, Singles, Premium)",
            "Count Remaining Grommets",
            "Check MDF Board Inventory",
            "Clean guide rail with alcohol or dust-free cloth (P1, P2, P3, P4, P5, P6)",
            "Clean UV Lamps with alcohol (P1, P2, P3, P4, P5, P6)"
        ]);
    }

    public function every2WeeksOnMonday(): void
    {
        $messages = [
            "Add lubricant to CNC Machines and Pumps"
        ];
        foreach ($messages as $message) {
        }
    }

    private function monthlyTasks(): void
    {
        $messages = [
            "Clean dustproof net on the water tank",
            "Replace water/coolant on all printers (P1, P2, P3, P4, P5, P6)",
            "Wipe printheads with cleaning solution (P1, P2, P3, P4, P5, P6)",
            "Check connections on headboard and mainboard (P1, P2, P3, P4, P5, P6)"
        ];
        foreach ($messages as $message) {
        }
    }

    private function every3MonthsTasks(): void
    {
        $messages = [
            "Replace antifreeze on P1, P2, P3, P4, P5, P6",
            "Lubricate all railing, axis (guide rail, Z axis rail, side rails) on P1, P2, P3, P4, P5, P6"
        ];
        foreach ($messages as $message) {
        }
    }

    private function scrapeFlatbed(): void
    {
        $message = "Scrape flatbeds & molds of ink: P1, P2, P3, P4, P5, P6";
    }

    private function cleanPrintersDust(): void
    {
        $message = "Clean P1, P2, P3, P4, P5, P6 of dust.";
    }

    private function cleanPrintersMold(): void
    {
        $message = "Clean P4 mold.";
    }

    private function nozzleCheck(): void
    {
        $message = "Nozzle Check P1, P2, P3, P4, P5, P6. Share Photo for each.";
    }

    private function requestNightShiftPhoto(): void
    {
        $message = "Request photo from Night Shift on what has been printed.";
    }

    private function checkAndRefillPrinterInk(): void
    {
        $message = "Check ink level and refill for each printer.";
    }
}
