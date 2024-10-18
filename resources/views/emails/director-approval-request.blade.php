<!-- resources/views/emails/director-approval-request.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Approval Request for Reservation</title>
</head>
<body>
    <h1>Final Approval Request for Reservation</h1>
    
    <p>All other signatories have approved this reservation. Please review the details below:</p>

    <p>The purpose of this reservation is for: {{ $reservation->booking->purpose ?? 'N/A' }}</p>

    <p>Booking Details:</p>
    <ul>
        <li>Requester: {{ $reservation->booking->user->name ?? 'N/A' }}</li>
        <li>Facility: {{ $reservation->booking->facility->facility_name ?? 'N/A' }}</li>
        <li>Time Start: {{ optional($reservation->booking->booking_start)->format('Y-m-d H:i') ?? 'N/A' }}</li>
        <li>Time End: {{ optional($reservation->booking->booking_end)->format('Y-m-d H:i') ?? 'N/A' }}</li>
    </ul>
    
    <p>Additional Details:</p>
    <ul>
        <li>Participants: {{ $reservation->booking->participants ?? 'N/A' }}</li>
        {{-- <li>Equipment: {{ $formattedEquipment }}</li>
        <li>Attachments: 
            @if($reservation->booking->attachments->isNotEmpty())
                {{ $reservation->booking->attachments->pluck('file_name')->join(', ') }}
            @else
                No attachments
            @endif
        </li> --}}
    </ul>
    
    <p>Previous Approvals:</p>
    <ul>
    @forelse ($previousApprovals as $approval)
        <li>{{ $approval['role'] }}: {{ $approval['name'] }} 
            (Approved on {{ $approval['approval_date'] ? \Carbon\Carbon::parse($approval['approval_date'])->format('Y-m-d H:i') : 'N/A' }})
        </li>
    @empty
        <li>No previous approvals</li>
    @endforelse
    </ul>
    
    <p>To approve the request, click the approval link below:</p>
    <p><a href="{{ $approvalUrl }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Approve</a></p>
    
    <p>To deny the request, click the deny link below:</p>
    <p><a href="{{ $denialUrl }}" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Deny</a></p>
    
    <p>Thank you for your attention to this matter.</p>
</body>
</html>