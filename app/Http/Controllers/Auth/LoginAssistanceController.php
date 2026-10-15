<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Ticketing\Actions\CreateTicket;
use App\Domains\Ticketing\Entities\TicketCategory;
use App\Domains\Ticketing\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Models\Employee;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginAssistanceController extends Controller
{
    public function store(Request $request, CreateTicket $createTicket): RedirectResponse
    {
        $validated = $request->validate([
            'nik_or_email' => ['required', 'string', 'max:100'],
            'issue_type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:30'],
        ]);

        $input = trim($validated['nik_or_email']);
        $reporterId = $input;
        $reporterEmail = null;

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $input)->first();
            $reporterEmail = $input;
            if ($user && $user->employee) {
                $reporterId = $user->employee->nik ?? $user->employee->NIK;
            }
        } else {
            $employee = Employee::where('nik', $input)->first();
            if (! $employee) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['nik_or_email' => __('ticketing.login_help.nik_not_found')]);
            }

            $reporterId = $employee->nik;
            $user = User::where('employee_id', $employee->id)->orWhere('employee_id', $employee->nik)->first();
            if ($user && $user->email) {
                $reporterEmail = $user->email;
            }
        }

        // Locate Access & Permissions category or fallback
        $category = TicketCategory::where('name', 'like', '%Access%')
            ->orWhere('name', 'like', '%Permission%')
            ->first() ?? TicketCategory::first();

        $categoryId = $category ? $category->id : 1;

        $title = "[Login Help] {$validated['issue_type']} - {$input}";
        $fullDescription = "Issue Type: {$validated['issue_type']}\n"
            . 'Contact Number: ' . ($validated['contact_number'] ?: 'Not provided') . "\n\n"
            . "Problem Description:\n{$validated['description']}";

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

        return redirect()->route('login')->with(
            'status',
            __('ticketing.login_help.success_status', ['ticket' => $ticket->ticket_number])
        );
    }
}
