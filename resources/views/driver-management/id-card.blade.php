<!doctype html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 0;
            size: 144.2mm 104mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            width: 144.2mm;
            height: 104mm;
            font-family: 'DejaVu Sans', sans-serif;
            color: #1a1a1a;
            background: #ffffff;
            font-size: 6.5pt;
        }

        .sheet {
            width: 144.2mm;
            height: 104mm;
            position: relative;
            background: #ffffff;
            border: 1px solid #22222269 !important;
            overflow: hidden;
        }

        .panel {
            width: 72mm;
            height: 104mm;
            position: absolute;
            top: 0;
            background: #ffffff;
            overflow: hidden;
        }

        .front {
            left: 0;
        }

        .back {
            left: 72mm;
        }

        /* ---------------- FRONT PANEL ---------------- */

        /* Header curved shape */
        .front-header-curve {
            position: absolute;
            top: -24mm;
            left: -10mm;
            width: 95mm;
            height: 40mm;
            background: #005a9e;
            border-radius: 0 0 37mm 20mm;
            z-index: 1;
        }

        .front-header-gray {
            position: absolute;
            top: 5mm;
            right: -27px;
            width: 35mm;
            height: 12mm;
            background: #8d9499;
            border-bottom-left-radius: 23mm;
            z-index: 0;
            border-bottom-right-radius: 29mm;
        }



        .header-content {
            position: absolute;
            top: 1.8mm;
            left: 3mm;
            width: 80mm;
            z-index: 2;
        }

        .header-logo {
            width: 12mm;
            height: 12mm;
            vertical-align: middle;
        }

        .header-titles {
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            width: 40mm;
            margin-left: 9px;
        }

        .header-title-main {
            color: #ffffff;
            font-size: 17.5pt;
            font-weight: bold;
            line-height: 1.1;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ccc;
        }

        .header-title-sub {
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        /* Pill Badge */
        .badge-pill {
            position: absolute;
            top: 13.8mm;
            left: 18mm;
            background: #e6004c;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1mm 6mm;
            border-radius: 3.5mm;
            text-align: center;
            z-index: 5;
        }

        /* Vertical Brand Logo */
        .jbm-logo-box {
            position: absolute;
            left: 7.5mm;
            top: 20mm;
            width: 7mm;
            height: 20mm;
            z-index: 4;
            text-align: center;
        }

        .jbm-logo-img {
            max-width: 17mm;
            max-height: 28mm;
        }

        /* Photo Frame */
        .photo-frame {
            position: absolute;
            left: 19mm;
            top: 19.5mm;
            width: 33mm;
            height: 31mm;
            border: 1mm solid #00a0e3;
            border-radius: 0 6mm 0 6mm;
            overflow: hidden;
            background: #ffffff;
            z-index: 3;
        }

        .driver-photo {
            width: 100%;
            height: 100%;
        }

        /* Details */
        .driver-name {
            position: absolute;
            left: 11.5mm;
            top: 52.5mm;
            color: #e6004c;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            z-index: 4;
        }

        .details-list {
            position: absolute;
            left: 12.5mm;
            top: 58.5mm;
            width: 54mm;
            z-index: 4;
            line-height: 2.40;
            font-size: 6.5pt;
            font-weight: bold;
        }

        .details-list .col-label {
            color: #333333;
            display: inline-block;
            width: 20mm;
        }

        .details-list .col-val {
            color: #005a9e;
        }

        /* Signature */
        .signature-section {
            position: absolute;
            right: 7mm;
            bottom: 2mm;
            width: 25mm;
            text-align: center;
            z-index: 4;
        }

        .sig-img {
            width: 30mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .sig-line {
            border-top: 0.25mm solid #222222;
            font-size: 5.8pt;
            color: #222222;
            padding-top: 0.7mm;
            margin-top: 0.7mm;
            font-style: italic;
        }

        /* Bottom Waves */
        .bottom-wave-gray {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 20mm;
            height: 5.5mm;
            background: #7d848a;
            border-top-right-radius: 12mm 5mm;
            z-index: 1;
        }

        .bottom-wave-blue {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 18mm;
            height: 4mm;
            background: #005a9e;
            border-top-right-radius: 10mm 4mm;
            z-index: 2;
        }

        /* ---------------- BACK PANEL ---------------- */

        .back-divider {
            position: absolute;
            left: 0;
            top: 2.5mm;
            bottom: 2.5mm;
            border-left: 0.25mm solid #b0b0b0;
        }

        .instructions-heading {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            color: #111111;
            text-decoration: underline;
            margin-top: 2.5mm;
            letter-spacing: 0.3px;
        }

        .instructions-list {
            margin: 1.5mm 4mm 0 4mm;
            list-style: none;
        }

        .instruction-row {
            position: relative;
            margin-bottom: 0.7mm;
            padding-left: 4mm;
            line-height: 2.18;
            font-size: 5.1pt;
            color: #111111;
            font-weight: bold;
        }

        .bullet-diamond {
            position: absolute;
            top: 0;
            left: 0;
            color: #f5a623;
            font-size: 5.5pt;
            width: 2.5mm;
        }

        .instruction-text {
            display: block;
            width: 56mm;
            font-size: 9px;
            line-height: 16px;
        }

        .company-block {
            position: absolute;
            bottom: 5mm;
            left: 3mm;
            right: 3mm;
            text-align: center;
        }

        .company-logo {
            width: 28mm;
            height: 17mm;
            display: block;
            margin: 0 auto 0.5mm auto;
        }

        .company-title {
            color: #0b3d68;
            font-size: 14.8pt;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: 0.2px;
        }

        .company-branch {
            color: #e6004c;
            font-size: 5.5pt;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 0.5mm;
        }

        .company-address {
            font-size: 5pt;
            font-weight: bold;
            line-height: 1.15;
            color: #111111;
            margin-top: 0.3mm;
            max-width: 60mm;
            padding-left: 10mm;
            margin: 5px 0;
        }

        .company-contact {
            color: #005a9e;
            font-size: 5.8pt;
            font-weight: 900;
            margin-top: 0.3mm;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <!-- FRONT -->
        <div class="panel front">
            <div class="front-header-gray"></div>
            <div class="front-header-curve"></div>

            <div class="header-content">
                <img class="header-logo" src="{{ $tgsrtcLogo }}" alt="TGSRTC Logo">
                <div class="header-titles">
                    <div class="header-title-main">TGSRTC</div>
                    <div class="header-title-sub">{{ $depot }}</div>
                </div>
            </div>

            <div class="badge-pill">EMPLOYEE ID CARD</div>

            <div class="jbm-logo-box">
                <img class="jbm-logo-img" src="{{ $jbmLogo }}" alt="JBM">
            </div>

            <div class="photo-frame">
                <img class="driver-photo" src="{{ $photo }}" alt="Photo">
            </div>

            <div class="driver-name">{{ $record->name ?: 'KOLIPAKA RAMARAJU' }}</div>

            <div class="details-list">
                <div>
                    <span class="col-label">Father's Name</span>:
                    <span
                        class="col-val">{{ $profile?->emergency_contact_name ?: ($record->emergency_contact_name ?? 'K. Shekharaiah') }}</span>
                </div>
                <div>
                    <span class="col-label">Staff ID</span>:
                    <span class="col-val">{{ $record->code ?: '-' }}</span>
                </div>
                <div>
                    <span class="col-label">Designation</span>:
                    <span class="col-val">EV-BUS Driver</span>
                </div>
                <div>
                    <span class="col-label">Contact No.</span>:
                    <span class="col-val">{{ $record->full_phone ?: '-' }}</span>
                </div>
            </div>

            <div class="signature-section">
                @if(!empty($signature))
                    <img class="sig-img" src="{{ $signature }}" alt="Signature">
                @else
                    <div style="height: 4.5mm;"></div>
                @endif
                <div class="sig-line">Authorised Signature</div>
            </div>

            <div class="bottom-wave-gray"></div>
            <div class="bottom-wave-blue"></div>
        </div>

        <!-- BACK -->
        <div class="panel back">
            <div class="back-divider"></div>

            <div class="instructions-heading">INSTRUCTIONS</div>

            <div class="instructions-list">
                @foreach ($instructions as $instruction)
                    <div class="instruction-row">
                        <span class="bullet-diamond">&#9670;</span>
                        <span class="instruction-text">{{ $instruction }}</span>
                    </div>
                @endforeach
            </div>

            <div class="company-block">
                <img class="company-logo" src="{{ $sysconLogo }}" alt="Syscon">
                <div class="company-title">SYSCON FUNCTIONAL NETWORKS PVT LTD</div>
                <div class="company-branch">Branch Office</div>
                <div class="company-address">
                    {{ $address ?: "TGSRTC, Warangal -2 Depot, Kakaji Colony, Hanamkonda, Warangal Telangana-506001." }}
                </div>
                <div class="company-contact">Ph.No. 9985594222</div>
            </div>
        </div>
    </div>
</body>

</html>
