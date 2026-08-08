<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk QR Codes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .page-break {
            page-break-after: always;
        }
        .qr-card {
            width: 30%;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin: 1%;
            text-align: center;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }
        .qr-image {
            width: 150px;
            height: 150px;
            margin: 0 auto 10px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .qr-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .name {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #333;
        }
        .id-number {
            font-size: 12px;
            color: #666;
            font-family: monospace;
            margin: 0 0 5px 0;
        }
        .meta {
            font-size: 11px;
            color: #888;
        }
        .row {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Student QR Codes</h2>
    
    <div>
        @foreach($students as $index => $student)
            <div class="qr-card">
                <div class="qr-image">
                    @if($student->qr_code_path && file_exists(storage_path('app/public/'.$student->qr_code_path)))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$student->qr_code_path))) }}" alt="QR Code">
                    @else
                        <!-- We cannot reliably use external URLs in DOMPDF without allow_url_fopen enabled, so we will generate it inline using a local package if possible, but DOMPDF does support external URLs if configured. For safety, we use the QR server API. -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($student->student_number ?? $student->id) }}" alt="QR Code">
                    @endif
                </div>
                <p class="name">{{ $student->name }}</p>
                <p class="id-number">{{ $student->student_number ?? 'ID: '.$student->id }}</p>
                <p class="meta">{{ $student->course ?? 'N/A' }} - Year {{ $student->year_level ?? 'N/A' }}</p>
            </div>
            
            @if(($index + 1) % 15 == 0 && !$loop->last)
                <div class="page-break"></div>
                <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Student QR Codes (Cont.)</h2>
            @endif
        @endforeach
    </div>
</body>
</html>
