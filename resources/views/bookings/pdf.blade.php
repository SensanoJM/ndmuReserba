<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 25px;
        }

        /* Page break utilities */
        .page-break {
            page-break-after: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        /* Header styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .logo {
            max-width: 80px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
        }

        /* Section styles */
        .section {
            margin-bottom: 15px;
            padding: 10px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
            color: #2d3748;
        }

        /* Grid layout */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .info-item {
            margin-bottom: 5px;
            font-size: 12px;
        }

        .label {
            font-weight: bold;
            color: #4a5568;
            min-width: 100px;
            display: inline-block;
        }

        .value {
            margin-left: 5px;
        }

        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .table th, .table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2d3748;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Signatures section */
        .signatures {
            margin-top: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
        }

        .signature-block {
            margin-bottom: 30px;
            text-align: center;
            width: 45%;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 12px;
            font-weight: bold;
        }

        .signature-title {
            margin-bottom: 30px;
            font-size: 11px;
            color: #666;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 10px;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 12px; }
        .text-xs { font-size: 10px; }
        .mb-2 { margin-bottom: 8px; }
        .p-2 { padding: 8px; }
    </style>
</head>
<body>
    <div class="avoid-break">
        <!-- Header -->
        <div class="header">
            @php
                $logoPath = public_path('storage/images/GreenKey.png');
                $hasLogo = file_exists($logoPath) && extension_loaded('gd');
            @endphp

            @if($hasLogo)
                <img src="{{ $logoPath }}" alt="Logo" class="logo">
            @else
                <div class="organization-name">Facility Management System</div>
            @endif
            
            <div class="title">Facility Booking Form</div>
            <div class="subtitle">Booking Reference: #{{ $booking->id }}</div>
        </div>

        <!-- Main Content -->
        <div class="section avoid-break">
            <div class="section-title">Basic Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Requester:</span>
                    <span class="value">{{ $booking->user->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Department:</span>
                    <span class="value">{{ $getDepartmentName($booking->user) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Facility:</span>
                    <span class="value">{{ $booking->facility->facility_name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Purpose:</span>
                    <span class="value">{{ $booking->purpose ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="section-title">Booking Schedule</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Start:</span>
                    <span class="value">{{ optional($booking->booking_start)->format('M d, Y h:i A') ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">End:</span>
                    <span class="value">{{ optional($booking->booking_end)->format('M d, Y h:i A') ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Duration:</span>
                    <span class="value">{{ $booking->duration ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Participants:</span>
                    <span class="value">{{ $booking->participants ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        @if($booking->equipment && $booking->equipment->isNotEmpty())
        <div class="section avoid-break">
            <div class="section-title">Equipment Requirements</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="70%">Equipment</th>
                        <th width="30%">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->equipment as $equipment)
                    <tr>
                        <td>{{ $equipment->name ?? 'N/A' }}</td>
                        <td>{{ $equipment->pivot->quantity ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section avoid-break">
            <div class="section-title">Approval Status</div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="30%">Role</th>
                        <th width="30%">Status</th>
                        <th width="40%">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($signatories as $signatory)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $signatory->role ?? 'N/A')) }}</td>
                        <td>{{ ucfirst($signatory->status ?? 'N/A') }}</td>
                        <td class="text-sm">{{ optional($signatory->approval_date)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="signatures avoid-break">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $booking->user->name ?? 'N/A' }}</div>
                <div class="signature-title">Requester</div>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Facilities Management</div>
                <div class="signature-title">Authorized Representative</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="text-xs">Generated on {{ now()->format('F d, Y h:i A') }}</div>
        <div class="text-xs">This is a computer-generated document. No signature is required.</div>
    </div>
</body>
</html>