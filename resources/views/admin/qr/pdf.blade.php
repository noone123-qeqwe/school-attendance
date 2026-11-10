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
                    <img src="{{ $qrImages[$student->id] }}" alt="QR code for {{ $student->name }}">

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
