<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Report') - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 12px; 
            margin: 0; 
            padding: 0;
            color: #333;
            line-height: 1.4;
            background: white;
        }
        
        /* Logo Watermark */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            background: url('file://{{ public_path('images/logo.png') }}') center center no-repeat;
            background-size: contain;
            opacity: 0.04;
            z-index: -1;
            pointer-events: none;
        }
        
        .page-container {
            position: relative;
            z-index: 1;
            background: white;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Clean Branded Header */
        .pdf-header { 
            background: #faf9f7;
            border-bottom: 3px solid #800000;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 18px;
            justify-content: center;
            position: relative;
        }
        
        .pdf-header-logo {
            width: 72px;
            height: 72px;
            background: url('file://{{ public_path('images/logo.png') }}') center center no-repeat;
            background-size: contain;
            border-radius: 16px;
            border: 2px solid #800000;
            flex-shrink: 0;
        }
        
        .pdf-header-text {
            text-align: left;
            max-width: 100%;
        }
        
        .pdf-header .school-name {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #1f2937;
        }
        
        .pdf-header .school-tagline {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 12px;
        }
        
        .pdf-header .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            color: #111827;
            letter-spacing: 0.02em;
        }
        
        .pdf-header .date { 
            font-size: 10px; 
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* Content Area */
        .pdf-content {
            padding: 30px 40px;
            background: white;
        }
        
        /* Simple Info Section */
        .info-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-left: 4px solid #800000;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-title {
            font-weight: bold;
            color: #800000;
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .filter-tag {
            background: white;
            border: 1px solid #ccc;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            color: #555;
            display: inline-block;
            margin: 2px 4px 2px 0;
        }
        
        /* Simple Statistics */
        .stats-grid {
            width: 100%;
            margin-bottom: 25px;
        }
        
        .stat-card {
            display: inline-block;
            width: 23%;
            margin: 0 1% 10px 0;
            text-align: center;
            background: white;
            border: 1px solid #ddd;
            padding: 15px 10px;
            vertical-align: top;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #800000;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        /* Simple Table */
        .pdf-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .pdf-table th { 
            background: #f5f5f5;
            color: #333; 
            font-weight: bold;
            padding: 12px 8px;
            text-align: left;
            font-size: 11px;
            border-bottom: 2px solid #800000;
        }
        
        .pdf-table td { 
            padding: 10px 8px; 
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        
        .pdf-table tr:nth-child(even) { 
            background: #fafafa;
        }
        
        /* Simple Badges */
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 40px;
        }
        
        .badge-course { background: #e3f2fd; color: #1976d2; }
        .badge-year { background: #e8f5e8; color: #388e3c; }
        .badge-semester { background: #fff3e0; color: #f57c00; }
        .badge-present { background: #e8f5e8; color: #388e3c; }
        .badge-late { background: #fff3e0; color: #f57c00; }
        .badge-absent { background: #ffebee; color: #d32f2f; }
        
        /* Simple Footer */
        .pdf-footer { 
            margin-top: 30px; 
            padding: 20px 40px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
            text-align: center; 
            color: #666; 
            font-size: 10px;
        }
        
        .pdf-footer .footer-brand {
            font-weight: bold;
            color: #800000;
            margin-bottom: 3px;
        }
        
        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-maroon { color: #800000; }
        .text-gray { color: #666; }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data h3 {
            color: #800000;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .no-data p {
            font-size: 12px;
            color: #888;
        }
        
        /* Print Optimizations */
        @media print {
            body { margin: 0; background: white; }
            .pdf-header { page-break-after: avoid; }
            .pdf-table tr { page-break-inside: avoid; }
        }
        
        @yield('additional-styles')
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header with Logo -->
        <div class="pdf-header">
            <div class="pdf-header-logo"></div>
            <div class="pdf-header-text">
                <div class="school-name">{{ config('app.name', 'School Attendance') }}</div>
                <div class="school-tagline">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</div>
                <div class="report-title">@yield('report-title', 'Report')</div>
                <div class="date">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>

        <!-- Content -->
        <div class="pdf-content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="pdf-footer">
            <div class="footer-brand">{{ config('app.name') }} • @yield('footer-title', 'Report')</div>
            <div>@yield('footer-details') • Generated {{ now()->format('M d, Y \a\t g:i A') }}</div>
        </div>
    </div>
</body>
</html>