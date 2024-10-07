<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Request for Reservation</title>
</head>
<body>
    <h1>Approval Request for Reservation</h1>
    
    <p>Please review the reservation details below:</p>
    
    <h2>Booking Details:</h2>
    <ul>
        <li>User: {{ $reservation->booking->user->name ?? 'N/A' }}</li>
        <li>Facility: {{ $reservation->booking->facility->facility_name ?? 'N/A' }}</li>
        <li>Date: {{ optional($reservation->booking->booking_date)->format('Y-m-d') ?? 'N/A' }}</li>
        <li>Time: {{ optional($reservation->booking->start_time)->format('H:i') ?? 'N/A' }} - 
            {{ optional($reservation->booking->end_time)->format('H:i') ?? 'N/A' }}</li>
    </ul>
    
    <p>To approve the request, click the approval link below:</p>
    
    <p><a href="{{ $approvalUrl }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Approve</a></p>
    
    <p>To deny the request, click the deny link below:</p>
    
    <p><a href="{{ $denialUrl }}" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Deny</a></p>
    
    <p>Thank you for your attention to this matter.</p>
</body>
</html>