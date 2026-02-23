<?php

namespace App\Enum;

use App\Entity\OrderLog;

enum DesignerTaskEnum: string
{
    case PROOF = 'proof';
    case PRINT_FILE = 'print_file';
    case REVIEWER_PROOF = 'reviewer_proof';
    case REVIEWER_PRINT_FILE = 'reviewer_print_file';

    public function label(): string
    {
        return match ($this) {
            self::PROOF => 'Proof',
            self::PRINT_FILE => 'Print File',
            self::REVIEWER_PROOF => 'Reviewer Proof',
            self::REVIEWER_PRINT_FILE => 'Reviewer Print File',
        };
    }

    public function logType(): string
    {
        return match ($this) {
            self::PROOF => OrderLog::DESIGNER_PROOF_STATUS,
            self::PRINT_FILE => OrderLog::DESIGNER_PRINT_FILE_STATUS,
            self::REVIEWER_PROOF => OrderLog::DESIGNER_REVIEWER_PROOF_STATUS,
            self::REVIEWER_PRINT_FILE => OrderLog::DESIGNER_REVIEWER_PRINT_FILE_STATUS,
        };
    }
}
