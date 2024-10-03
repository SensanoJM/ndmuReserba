@component('mail::message')
# Director Approval Request for Reservation

Dear Director,

A reservation requires your final approval. All other signatories have approved this request.

## Booking Details:
- User: {{ $reservation->booking->user->name ?? 'N/A' }}
- Facility: {{ $reservation->booking->facility->facility_name ?? 'N/A' }}
- Date: {{ optional($reservation->booking->booking_date)->format('Y-m-d') ?? 'N/A' }}
- Time: {{ optional($reservation->booking->start_time)->format('H:i') ?? 'N/A' }} -
        {{ optional($reservation->booking->end_time)->format('H:i') ?? 'N/A' }}

## Previous Approvals:
@foreach ($previousApprovals as $approval)
- {{ $approval['role'] }}: {{ $approval['name'] }} (Approved on {{ $approval['approved_at']->format('Y-m-d H:i') }})
@endforeach

Please review the details and make your decision:

@component('mail::button', ['url' => $approvalUrl])
Approve
@endcomponent

@component('mail::button', ['url' => $denialUrl])
Deny
@endcomponent

Thank you for your attention to this matter.

@endcomponent