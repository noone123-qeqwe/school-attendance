<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Excuse Letter</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .guest-card { max-width: 500px; margin: 2rem auto; background: white; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
        .guest-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 24px; text-align: center; }
        .guest-body { padding: 32px; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 0.95rem; }
        .form-control:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
        .btn-primary { background-color: #3b82f6; border: none; border-radius: 10px; padding: 12px; font-weight: 600; transition: all 0.2s; }
        .btn-primary:hover { background-color: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
    </style>
</head>
<body>
    <div class="container">
        <div class="guest-card">
            <div class="guest-header">
                <i class="bi bi-file-earmark-medical-fill" style="font-size: 2.5rem; color: #60a5fa;"></i>
                <h4 class="mt-2 mb-0">Submit Excuse Letter</h4>
                <p class="mb-0 text-white-50 small">School Attendance System</p>
            </div>
            <div class="guest-body">
                <div class="alert alert-info" style="border-radius: 10px;">
                    <strong>Student:</strong> {{ $attendance->user->name }}<br>
                    <strong>Subject:</strong> {{ $attendance->subject_code }}<br>
                    <strong>Date:</strong> {{ $attendance->date->format('F j, Y') }}<br>
                    <strong>Status:</strong> <span class="badge bg-danger">{{ $attendance->status }}</span>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ request()->hasValidSignature() ? request()->fullUrl() : \Illuminate\Support\Facades\URL::signedRoute('guest.excuse.store', ['attendance' => $attendance->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Absence/Lateness</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Please provide details about why the student was absent or late..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Medical Certificate / Note (Optional)</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">Accepted formats: JPG, PNG, PDF (Max 5MB)</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send-fill me-2"></i> Submit Excuse
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
