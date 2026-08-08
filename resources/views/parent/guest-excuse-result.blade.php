<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excuse Submitted</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .result-card { max-width: 450px; background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); padding: 40px; text-align: center; }
        .icon-container { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .icon-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .icon-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    </style>
</head>
<body>
    <div class="result-card">
        @if(isset($status) && $status === 'success')
            <div class="icon-container icon-success">
                <i class="bi bi-check-lg" style="font-size: 2.5rem;"></i>
            </div>
            <h4 class="fw-bold mb-3">Submitted Successfully</h4>
        @else
            <div class="icon-container icon-info">
                <i class="bi bi-info-lg" style="font-size: 2.5rem;"></i>
            </div>
            <h4 class="fw-bold mb-3">Notice</h4>
        @endif
        
        <p class="text-muted mb-0">{{ $message ?? 'Your action was processed.' }}</p>
    </div>
</body>
</html>
