<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\Donation;
use Tests\TestCase;

class ReportsApiTest extends ApiTestCase
{
    /**
     * Test GET /api/reports/donation-ledger - Returns donation ledger with correct structure
     */
    public function test_get_reports_donation_ledger_returns_ledger(): void
    {
        $admin = $this->createUser('admin');
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'completed',
            'verified' => true,
        ]);

        $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'donor' => [
                            'id',
                            'name',
                        ],
                        'amount',
                        'verified',
                    ],
                ],
                'summary' => [
                    'total_amount',
                    'verified_amount',
                    'pending_amount',
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/reports/donation-ledger - Query parameters work correctly
     */
    public function test_get_reports_donation_ledger_with_query_parameters(): void
    {
        $admin = $this->createUser('admin');
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'completed',
            'verified' => true,
            'created_at' => '2025-01-20 10:00:00',
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 300.00,
            'payment_method' => 'stc_pay',
            'status' => 'completed',
            'verified' => false,
            'created_at' => '2025-01-21 10:00:00',
        ]);

        // Test date_from and date_to
        $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger?date_from=2025-01-01&date_to=2025-01-31', $admin);
        $response->assertStatus(200);

        // Test verified filter
        $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger?verified=true', $admin);
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $donation) {
            $this->assertTrue($donation['verified']);
        }

        // Test pagination
        $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger?page=1&per_page=50', $admin);
        $response->assertStatus(200);
        $this->assertArrayHasKey('current_page', $response->json('data'));
    }

    /**
     * Test GET /api/reports/donation-ledger - Returns 403 for unauthorized roles
     */
    public function test_get_reports_donation_ledger_returns_403_for_unauthorized_roles(): void
    {
        $unauthorizedRoles = ['investor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/reports/export/pdf - Exports PDF report
     */
    public function test_get_reports_export_pdf_exports_pdf(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/reports/export/pdf', $admin);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test GET /api/reports/export/pdf - Query parameters work correctly
     */
    public function test_get_reports_export_pdf_with_query_parameters(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/reports/export/pdf?date_from=2025-01-01&date_to=2025-01-31&verified=true', $admin);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test GET /api/reports/export/excel - Exports Excel report
     */
    public function test_get_reports_export_excel_exports_excel(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/reports/export/excel', $admin);

        $response->assertStatus(200);
        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(
            str_contains($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') ||
            str_contains($contentType, 'application/vnd.ms-excel')
        );
    }

    /**
     * Test GET /api/reports/export/excel - Query parameters work correctly
     */
    public function test_get_reports_export_excel_with_query_parameters(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/reports/export/excel?date_from=2025-01-01&date_to=2025-01-31&verified=true', $admin);

        $response->assertStatus(200);
    }
}

