<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Request for Reservation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .details table { width: 100%; border-collapse: collapse; }
        .details table td, .details table th { 
            padding: 8px; 
            border: 1px solid #ddd; 
        }
        .details table th { 
            background-color: #f8f9fa;
            text-align: left;
            width: 30%;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .button.approve { background-color: #28a745; }
        .button.deny { background-color: #dc3545; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Approval Request for Reservation</h1>
            <p>Please review the reservation details below:</p>
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>Requester:</th>
                    <td>{{ $reservation->booking->user->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Requester Email:</th>
                    <td>{{ $reservation->booking->user->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Requester Contact Number:</th>
                    <td>{{ $reservation->booking->contact_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Facility:</th>
                    <td>{{ $reservation->booking->facility->facility_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Purpose:</th>
                    <td>{{ $reservation->booking->purpose ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td>{{ optional($reservation->booking->booking_start)->format('F j, Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Time:</th>
                    <td>
                        {{ optional($reservation->booking->booking_start)->format('g:i A') ?? 'N/A' }} - 
                        {{ optional($reservation->booking->booking_end)->format('g:i A') ?? 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th>Participants:</th>
                    <td>{{ $reservation->booking->participants ?? 'N/A' }}</td>
                </tr>
                @if(isset($formattedEquipment) && $formattedEquipment !== 'No equipment requested')
                <tr>
                    <th style="text-align: left; padding-right: 10px;">Equipment:</th>
                    <td>
                        <ul style="list-style-type: disc; margin: 0; padding-left: 20px;">
                            @foreach(explode($formattedEquipment) as $equipment)
                                <li style="margin-bottom: 5px;">{{ $equipment }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <div style="text-align: center;">
            <p>To approve this request, please click the button below:</p>
            <a href="{{ $approvalUrl }}" class="button approve">Approve</a>
            
            <p>If you need to deny this request, click here:</p>
            <a href="{{ $denialUrl }}" class="button deny">Deny Request</a>
        </div>

        <div class="footer">
            <p>Thank you for your attention to this matter.</p>
            <p>This is an automated message, please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>