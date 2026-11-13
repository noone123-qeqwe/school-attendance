<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FaceAttendanceConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaceAttendanceConsentController extends Controller
{
    public function store(Request $request, User $student, FaceAttendanceConsent $consent): RedirectResponse
    {
        $input = $request->validate([
            'source' => ['required', Rule::in(FaceAttendanceConsent::SOURCES)],
            'documented' => ['required', 'accepted'],
        ]);
        $consent->update($student, $request->user(), $input['source']);

        return back()->with('success', 'Camera attendance consent recorded.');
    }

    public function destroy(Request $request, User $student, FaceAttendanceConsent $consent): RedirectResponse
    {
        $consent->update($student, $request->user(), null);

        return back()->with('success', 'Camera attendance consent withdrawn.');
    }

    public function withdraw(Request $request, FaceAttendanceConsent $consent): RedirectResponse
    {
        $student = $request->user();
        $consent->update($student, $student, null);

        return redirect()->route('home')->with('success', 'Camera attendance consent withdrawn. Ask your teacher for help recording attendance.');
    }
}
