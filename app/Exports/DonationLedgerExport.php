<?php

namespace App\Exports;

use App\Models\Donation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DonationLedgerExport implements FromCollection, WithHeadings, WithMapping
{
    protected $donations;

    public function __construct($donations)
    {
        $this->donations = $donations;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->donations;
    }

    /**
     * Column headings EXACTLY as specified in the spec.
     * Order: ID, Date, Donor Name, Amount, Verified
     */
    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Donor Name',
            'Amount',
            'Verified',
        ];
    }

    /**
     * Map each donation row EXACTLY as specified.
     * Order matches headings: ID, Date, Donor Name, Amount, Verified
     */
    public function map($donation): array
    {
        return [
            $donation->id,
            $donation->created_at->format('Y-m-d'),
            $donation->donor->name,
            number_format($donation->amount, 2, '.', ''),
            $donation->verified ? 'Yes' : 'No',
        ];
    }
}

