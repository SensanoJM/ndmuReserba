<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Approval Request for Reservation</title>
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
        .approvals { 
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Final Approval Request for Reservation</h1>
            <p>All other signatories have approved this reservation. Please review the details below:</p>
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>Requester:</th>
                    <td>{{ $reservation->booking->user->name ?? 'N/A' }}</td>
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
                            @foreach(explode(' • ', $formattedEquipment) as $equipment)
                                <li style="margin-bottom: 5px;">{{ $equipment }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <div class="approvals">
            <h3>Previous Approvals:</h3>
            <ul>
                @foreach($previousApprovals as $approval)
                    <li>
                        {{ ucfirst($approval['role']) }}: {{ $approval['name'] }}
                        (Approved on {{ \Carbon\Carbon::parse($approval['approval_date'])->format('M j, Y g:i A') }})
                    </li>
                @endforeach
            </ul>
        </div>

        <div style="text-align: center;">
            <p>To approve this request, please click the button below to review and provide your digital signature:</p>
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