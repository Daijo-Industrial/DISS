<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\Ticketing\Actions\CreateTicket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LoginAssistanceController extends Controller
{
    public function store(Request $request, CreateTicket $createTicket): RedirectResponse
    {
        $rawNik = $request->input('nik') ?? $request->input('nik_or_email');
        if (! $rawNik) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['nik' => __('validation.required', ['attribute' => 'NIK'])]);
        }

        $input = trim((string) $rawNik);

        // Corporate email disabled: strictly enforce NIK
        if (str_contains($input, '@') || filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'nik' => __('ticketing.login_help.corporate_email_disabled'),
                    'nik_or_email' => __('ticketing.login_help.corporate_email_disabled'),
                ]);
        }

        $validated = $request->validate([
            'issue_type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        $employee = Employee::where('nik', $input)->first();
        if (! $employee) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'nik' => __('ticketing.login_help.nik_not_found'),
                    'nik_or_email' => __('ticketing.login_help.nik_not_found'),
                ]);
        }

        $reporterId = $employee->nik;
        $user = User::where('employee_id', $employee->id)->orWhere('employee_id', $employee->nik)->first();
        $reporterEmail = $user?->email;

        // Dynamically resolve active ticket category based on issue type or default to Access & Permissions
        $activeCategories = TicketCategory::where('is_active', true);
        $issueType = $validated['issue_type'];
        $category = null;

        if (stripos($issueType, 'Hardware') !== false || stripos($issueType, 'Printer') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Hardware%')->first();
        } elseif (stripos($issueType, 'Network') !== false || stripos($issueType, 'Wi-Fi') !== false || stripos($issueType, 'Connectivity') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Network%')->first();
        } elseif (stripos($issueType, 'Software') !== false || stripos($issueType, 'Application') !== false) {
            $category = (clone $activeCategories)->where('name', 'like', '%Software%')->first();
        } elseif (stripos($issueType, 'Inquiry') !== false || stripos($issueType, 'Other') !== false) {
            $category = (clone $activeCategories)->where(function ($q) {
                $q->where('name', 'like', '%General%')->orWhere('name', 'like', '%Other%');
            })->first();
        }

        if (! $category) {
            $category = (clone $activeCategories)->where(function ($q) {
                $q->where('name', 'like', '%Access%')->orWhere('name', 'like', '%Permission%');
            })->first() ?? (clone $activeCategories)->first() ?? TicketCategory::first();
        }

        $categoryId = $category ? $category->id : 1;

        $title = "[IT Support] {$validated['issue_type']} - {$input}";
        $userDescription = trim($validated['description']);
        $contactNumber = ! empty($validated['contact_number']) ? trim($validated['contact_number']) : null;

        // Clean and readable description format aligned with IT Concierge
        $fullDescription = $contactNumber
            ? "{$userDescription}\n\nContact Number: {$contactNumber}"
            : $userDescription;

        $deviceInfo = $request->header('User-Agent');

        $ticket = $createTicket->execute(
            reporterId: $reporterId,
            categoryId: $categoryId,
            title: $title,
            description: $fullDescription,
            priority: TicketPriority::HIGH,
            reporterEmail: $reporterEmail,
            currentUrl: route('login'),
            screenshotPath: null,
            deviceInfo: $deviceInfo
        );

        return redirect()->route('login')
            ->with('status', __('ticketing.login_help.success_status', ['ticket' => $ticket->ticket_number]))
            ->with('submitted_ticket_number', $ticket->ticket_number)
            ->with('submitted_ticket_nik', $employee->nik);
    }
}
