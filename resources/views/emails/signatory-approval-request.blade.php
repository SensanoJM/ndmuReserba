<!-- resources/views/emails/signatory-approval-request.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Request for Reservation</title>
</head>
<body>
    <h1>Approval Request for Reservation</h1>
    
    <p>A reservation requires your approval:</p>
    
    <p>Booking Details:</p>
    <ul>
        <li>User: {{ $reservation->booking->user->name ?? 'N/A' }}</li>
        <li>Facility: {{ $reservation->booking->facility->facility_name ?? 'N/A' }}</li>
        <li>Date: {{ optional($reservation->booking->booking_date)->format('Y-m-d') ?? 'N/A' }}</li>
        <li>Time: {{ optional($reservation->booking->start_time)->format('H:i') ?? 'N/A' }} -
            {{ optional($reservation->booking->end_time)->format('H:i') ?? 'N/A' }}
        </li>
    </ul>
    
    <p>Please click the link below to approve or deny this reservation:</p>
    
    <a href="{{ $approvalUrl }}">Review Reservation</a>
    
    <p>Thank you for your attention to this matter.</p>
</body>
</html>