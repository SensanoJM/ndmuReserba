<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form</title>
    <style>
        /* Reset and base styles compatible with DomPDF */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 30px;
        }

        /* Header styles */
        .letterhead {
            width: 100%;
            position: relative;
            margin-bottom: 10px;
            height: 100px;
            /* Fixed height for letterhead */
        }

        .logo-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
        }

        .logo {
            width: 100px;
            height: 100px;
        }

        .contact-left {
            position: absolute;
            left: 90px;
            /* Space after logo */
            top: 60px;
            font-size: 7pt;
            line-height: 1.3;
        }

        .university-info {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            top: 0;
        }

        .university-name {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .university-address {
            font-size: 9pt;
            line-height: 1.3;
        }

        .contact-right {
            position: absolute;
            right: 70px;
            top: 70px;
            text-align: right;
            font-size: 7pt;
            line-height: 1.3;
        }

        /* Form header info */
        .form-header {
            margin-bottom: 15px;
        }

        .participants {
            float: left;
            width: 50%;
        }

        .instruction {
            float: right;
            width: 60%;
            font-size: 9pt;
            font-style: italic;
            text-align: right;
        }

        .form-title {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            margin: 20px 0;
        }

        /* Form fields */
        .form-group {
            clear: both;
            margin-bottom: 10px;
        }

        .field-label {
            float: left;
            width: 200px;
            font-size: 10pt;
            font-weight: bold;
        }

        .field-value {
            margin-left: 210px;
            border-bottom: 1px dotted #000;
            font-size: 10pt;
            min-height: 18px;
        }

        .field-valuee {
            margin-left: 240px;
            border-bottom: 1px dotted #000;
            font-size: 10pt;
            min-height: 18px;
        }

        .description-field {
            font-size: 9pt;
        }

        .date-time-group {
            margin-bottom: 10px;
        }

        .date-field {
            float: left;
            width: 60%;
        }

        .time-field {
            float: right;
            width: 40%;
        }

        /* Requirements section */
        .equipment-section {
            margin: 15px 0;
        }

        .equipment-label {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 5px;
            float: left;
            width: 200px;
        }

        .equipment-list {
            margin-left: 210px;
        }

        .equipment-item {
            border-bottom: 1px dotted #000;
            padding: 2px 0;
            margin-bottom: 5px;
            font-size: 10pt;
        }

        .no-equipment {
            border-bottom: 1px dotted #000;
            padding: 2px 0;
            font-style: italic;
            color: #666;
            font-size: 10pt;
        }

        /* Requirements section */
        .requirements {
            margin: 15px 0;
        }

        .requirements-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .checkbox-list {
            list-style: none;
        }

        .checkbox-item {
            margin-bottom: 7px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
        }

        /* Declaration text */
        .declaration {
            margin: 20px 0;
            font-size: 10pt;
            font-style: italic;
            text-align: justify;
        }

        .section {
            margin-bottom: 15px;
            padding: 10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2d3748;
        }


        .signatures {
            margin-top: 30px;
            width: 100%;
        }

        .signature-column {
            width: 50%;
            float: left;
            padding-right: 20px;
        }

        .signature-block {
            margin-bottom: 30px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 3px;
        }

        .signature-name {
            font-size: 9pt;
            text-align: center;
        }

        .approval-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signature-title {
            font-size: 9pt;
            text-align: center;
            font-weight: bold;
        }

        .text-xs {
            font-size: 9px;
            margin-bottom: 3px;
        }

        /* Ensure page breaks don't occur within sections */
        .avoid-break {
            page-break-inside: avoid;
        }

        .verification-note {
            font-size: 8pt;
            color: #666;
            margin-top: 10px;
            text-align: center;
            border-top: 1px dashed #ddd;
            padding-top: 10px;
        }

        .footer {
        clear: both;
        margin-top: 20px;
        font-size: 8pt;
        position: relative;
    }

    .copy-section {
        display: block;
        margin-bottom: 5px;
    }

    .checkbox {
        display: inline-block;
        width: 10px;
        height: 10px;
        border: 1px solid #000;
        margin: 0 5px;
    }

    .copy-label {
        display: inline-block;
        margin-right: 15px;
    }

    .copy-item {
        display: inline-block;
        margin-right: 30px; /* Spacing between items */
    }

    .form-number {
        position: absolute;
        right: 0;
        top: 0;
        color: #666;
    }

    .end-footer {
        position: fixed;
        bottom: 20px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
        color: #666;
        width: 100%;
    }

    .text-xs {
        margin-bottom: 3px;
    }

    /* Add padding to main content to prevent overlap */
    body {
        margin-bottom: 100px; /* Space for fixed footer */
    }

        /* Clearfix for float layouts */
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    <div class="letterhead">
        <!-- Logo Section -->
        <div class="logo-section">
            @if (file_exists(public_path('storage/images/logo.png')))
                <img src="{{ public_path('storage/images/logo.png') }}" alt="Logo" class="logo">
            @endif
        </div>

        <!-- Left Contact Information -->
        <div class="contact-left">
            Telephone Nos: (+63 83) 228-2322<br>
            President's Telefax: (+63 83) 228-2976<br>
            Trunk lines: (+63 83) 228-2716, 228-2777 (President)
        </div>

        <!-- Center University Information -->
        <div class="university-info">
            <div class="university-name">NOTRE DAME OF MARBEL UNIVERSITY</div>
            <div class="university-address">
                Alunan Avenue, City of Koronadal 9506<br>
                South Cotabato, Philippines<br>
                Physical Plant, Security & Safety
            </div>
        </div>

        <!-- Right Contact Information -->
        <div class="contact-right">
            Fax No. (+63 83) 228-2819<br>
            www.ndmu.edu.ph
        </div>
    </div>
    <div class="signature-line"></div>

    <!-- Form Title -->
    <div class="form-title">BOOKING FORM</div>

    <!-- Form Header -->
    <div class="form-group">
        <div class="field-label">Date Reserved at PPO:</div>
        <div class="field-value">{{ $booking->created_at->format('M d, Y') }}</div>
    </div>
    <div class="form-header clearfix">
        <div class="participants">Number of Participants: {{ $booking->participants }}</div>
        <div class="instruction">(Booking application must be done 3 working days before the start of the activity)
        </div>
    </div>

    <!-- Main Form Fields -->
    <div class="form-group">
        <div class="field-label">Organization/Department/Club/Unit:</div>
        <div class="field-valuee">{{ $booking->user->name ?? 'N/A' }}</div>
    </div>

    <!-- Date and Time Fields -->
    <div class="date-time-group clearfix">
        <div class="date-field">
            <div class="field-label">Date Start:</div>
            <div class="field-value">{{ $booking->booking_start->format('M d, Y') }}</div>
        </div>
        <div class="time-field">
            <div class="field-label">Time Start:</div>
            <div class="field-value">{{ $booking->booking_start->format('h:i A') }}</div>
        </div>
    </div>

    <div class="date-time-group clearfix">
        <div class="date-field">
            <div class="field-label">Date End:</div>
            <div class="field-value">{{ $booking->booking_end->format('M d, Y') }}</div>
        </div>
        <div class="time-field">
            <div class="field-label">Time End:</div>
            <div class="field-value">{{ $booking->booking_end->format('h:i A') }}</div>
        </div>
    </div>

    <div class="form-group">
        <div class="field-label">Place/Venue:</div>
        <div class="field-value">{{ $booking->facility->facility_name }}</div>
    </div>

    <div class="form-group">
        <div class="field-label">Purpose/Activity:</div>
        <div class="field-value">{{ $booking->purpose }}</div>
    </div>

    <!-- Equipment Section HTML -->
    <div class="equipment-section">
        <div class="equipment-label">Facilities Requested:</div>
        <div class="equipment-list">
            @if ($booking->equipment && $booking->equipment->count() > 0)
                @foreach ($booking->equipment as $equipment)
                    <div class="equipment-item">
                        {{ ucwords(str_replace('_', ' ', $equipment->name)) }}
                        ({{ $equipment->pivot->quantity }} {{ $equipment->pivot->quantity > 1 ? 'units' : 'unit' }})
                    </div>
                @endforeach
            @else
                <div class="no-equipment">No equipment requested</div>
            @endif
        </div>
    </div>

    <!-- Section Divider -->
    <div class="signature-line"></div>

    <!-- Requirements Section -->
    <div class="requirements">
        <div class="requirements-title">Comply with the following checked items:</div>
        <ul class="checkbox-list">
            <li class="checkbox-item">
                <span class="checkbox"></span>
                Remove and keep all your clippings, props, decorations, buntings etc. right after the program
            </li>
            <li class="checkbox-item">
                <span class="checkbox"></span>
                Collect and dispose all garbage right after the program
            </li>
            <li class="checkbox-item">
                <span class="checkbox"></span>
                Restore the venue right after the program (arrange, clear and clean)
            </li>
        </ul>
    </div>

    <!-- Section Divider -->
    <div class="signature-line"></div>

    <!-- Declaration -->
    <div class="declaration">
        I have read, understood and shall endeavor to comply with all the requirements set forth in the guidelines for
        booking.
        Further, I shall take full responsibility and accountability of the outcome of this activity.<br><br>
        In Mary our good Mother and Saint Marcellin our Founder,
    </div>

    <!-- Approval Status Section -->
    <div class="section avoid-break">
        <div class="approval-title">Digital Approval Record</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="30%">Role</th>
                    <th width="30%">Status</th>
                    <th width="40%">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($signatories as $signatory)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $signatory->role ?? 'N/A')) }}</td>
                        <td>{{ ucfirst($signatory->status ?? 'N/A') }}</td>
                        <td>{{ optional($signatory->approval_date)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="verification-note">
            The authenticity of this document can be verified through the Facility Management System.
            Reference Number: #{{ $booking->id }}
        </div>
    </div>

    <!-- Footer -->
    <div class="footer clearfix">
        <div class="copy-section">
            <span class="copy-label">Copy for:</span>
            <span class="copy-item">
                <span class="checkbox"></span>APPLICANTS
            </span>
            <span class="copy-item">
                <span class="checkbox"></span>PHYSICAL PLANT OFFICE
            </span>
            <span class="copy-item">
                <span class="checkbox"></span>EMC
            </span>
            <span class="copy-item">
                <span class="checkbox"></span>DSA
            </span>
        </div>
    </div>

    <div class="end-footer">
        <div class="text-xs">Generated on {{ now()->format('F d, Y h:i A') }}</div>
        <div class="text-xs">This is a computer-generated document. No signature is required.</div>
    </div>

</body>

</html>
