<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PolicyService;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    protected PolicyService $policyService;

    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * Show policy editor page for administrators
     */
    public function edit()
    {
        $privacy = $this->policyService->getPrivacyPolicy();
        $terms = $this->policyService->getTermsAndConditions();

        return view('admin.policies.edit', compact('privacy', 'terms'));
    }

    /**
     * Update policies
     */
    public function update(Request $request)
    {
        $type = $request->input('policy_type', 'privacy');

        if ($type === 'privacy') {
            $request->validate([
                'privacy_content'        => 'required|string|min:50',
                'privacy_version'        => 'required|string|max:20',
                'privacy_effective_date' => 'required|date',
            ]);

            $this->policyService->updatePrivacyPolicy(
                $request->input('privacy_content'),
                $request->input('privacy_version'),
                $request->input('privacy_effective_date')
            );

            return redirect()->route('admin.policies.edit', ['tab' => 'privacy'])
                ->with('success', 'Privacy Notice updated and published successfully!');
        } else {
            $request->validate([
                'terms_content'        => 'required|string|min:50',
                'terms_version'        => 'required|string|max:20',
                'terms_effective_date' => 'required|date',
            ]);

            $this->policyService->updateTermsAndConditions(
                $request->input('terms_content'),
                $request->input('terms_version'),
                $request->input('terms_effective_date')
            );

            return redirect()->route('admin.policies.edit', ['tab' => 'terms'])
                ->with('success', 'Terms & Conditions updated and published successfully!');
        }
    }

    /**
     * Reset policy to system default
     */
    public function reset(Request $request)
    {
        $type = $request->input('type', 'privacy');
        $this->policyService->resetToDefaults($type);

        return redirect()->route('admin.policies.edit', ['tab' => $type])
            ->with('success', 'Policy content reset to system defaults.');
    }
}
