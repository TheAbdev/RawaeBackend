<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DonationLedgerExport;

class ReportsController extends Controller
{
    /**
     * Get donation ledger for reports (Admin/Auditor only).
     *
     * GET /api/reports/donation-ledger
     *
     * Query Parameters:
     * - date_from (date, optional)
     * - date_to (date, optional)
     * - verified (boolean, optional)
     * - page (integer, default: 1)
     * - per_page (integer, default: 50)
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "date": "2025-01-20",
     *         "donor": {
     *           "id": 4,
     *           "name": "Ahmed Ali"
     *         },
     *         "amount": "500.00",
     *         "verified": true
     *       }
     *     ],
     *     "summary": {
     *       "total_amount": 45230.00,
     *       "verified_amount": 40000.00,
     *       "pending_amount": 5230.00
     *     }
     *   }
     * }
     */
    public function donationLedger(Request $request): JsonResponse
    {
        // Check if user is admin or auditor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or auditor can access reports.',
            ], 403);
        }

        $query = Donation::with(['donor']);

        // Filter by date_from
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter by date_to
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by verified
        if ($request->has('verified') && $request->verified !== null) {
            $verified = filter_var($request->verified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($verified !== null) {
                $query->where('verified', $verified);
            }
        }

        // Clone query for summary calculation (before pagination)
        $summaryQuery = clone $query;
        $allDonations = $summaryQuery->get();

        // Calculate summary
        $totalAmount = $allDonations->sum('amount');
        $verifiedAmount = $allDonations->where('verified', true)->sum('amount');
        $pendingAmount = $allDonations->where('verified', false)->sum('amount');

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $donations = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Format response data exactly as specified
        $formattedData = collect($donations->items())->map(function ($donation) {
            return [
                'id' => $donation->id,
                'date' => $donation->created_at->format('Y-m-d'),
                'donor' => [
                    'id' => $donation->donor->id,
                    'name' => $donation->donor->name,
                ],
                'amount' => number_format($donation->amount, 2, '.', ''),
                'verified' => $donation->verified,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $formattedData,
                'summary' => [
                    'total_amount' => round($totalAmount, 2),
                    'verified_amount' => round($verifiedAmount, 2),
                    'pending_amount' => round($pendingAmount, 2),
                ],
            ],
        ], 200);
    }

    /**
     * Export donation ledger as PDF (Admin/Auditor only).
     *
     * GET /api/reports/export/pdf
     *
     * Query Parameters: Same as donation-ledger
     *
     * Response (200):
     * {
     *   "success": true,
     *   "file_url": "https://storage.example.com/reports/donation-ledger-2025-01-20.pdf"
     * }
     */
    public function exportPdf(Request $request)
    {
        // Check if user is admin or auditor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or auditor can export reports.',
            ], 403);
        }

        $query = Donation::with(['donor']);

        // Filter by date_from
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter by date_to
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by verified
        if ($request->has('verified') && $request->verified !== null) {
            $verified = filter_var($request->verified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($verified !== null) {
                $query->where('verified', $verified);
            }
        }

        // Get all donations (no pagination for export)
        $donations = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary
        $totalAmount = $donations->sum('amount');
        $verifiedAmount = $donations->where('verified', true)->sum('amount');
        $pendingAmount = $donations->where('verified', false)->sum('amount');

        // Format data for PDF
        $formattedData = $donations->map(function ($donation) {
            return [
                'id' => $donation->id,
                'date' => $donation->created_at->format('Y-m-d'),
                'donor_name' => $donation->donor->name,
                'amount' => number_format($donation->amount, 2, '.', ''),
                'verified' => $donation->verified ? 'Yes' : 'No',
            ];
        });

        // Generate PDF
        $pdf = Pdf::loadView('reports.donation-ledger-pdf', [
            'donations' => $formattedData,
            'summary' => [
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'verified_amount' => number_format($verifiedAmount, 2, '.', ''),
                'pending_amount' => number_format($pendingAmount, 2, '.', ''),
            ],
            'filters' => [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'verified' => $request->verified,
            ],
        ]);

        // Generate filename
        $filename = 'donation-ledger-' . date('Y-m-d-His') . '.pdf';

        // Return PDF directly as download (solves ngrok issue)
        return $pdf->download($filename);
    }

    /**
     * Export donation ledger as Excel (Admin/Auditor only).
     *
     * GET /api/reports/export/excel
     *
     * Query Parameters: Same as donation-ledger
     *
     * Response (200):
     * {
     *   "success": true,
     *   "file_url": "https://storage.example.com/reports/donation-ledger-2025-01-20.xlsx"
     * }
     */
    public function exportExcel(Request $request)
    {
        // Check if user is admin or auditor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or auditor can export reports.',
            ], 403);
        }

        $query = Donation::with(['donor']);

        // Filter by date_from
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter by date_to
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by verified
        if ($request->has('verified') && $request->verified !== null) {
            $verified = filter_var($request->verified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($verified !== null) {
                $query->where('verified', $verified);
            }
        }

        // Get all donations (no pagination for export)
        $donations = $query->orderBy('created_at', 'desc')->get();

        // Generate filename
        $filename = 'donation-ledger-' . date('Y-m-d-His') . '.xlsx';

        // Return Excel directly as download (solves ngrok issue)
        return Excel::download(new DonationLedgerExport($donations), $filename);
    }
}

