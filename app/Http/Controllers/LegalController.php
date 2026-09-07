<?php

namespace App\Http\Controllers;

use App\Services\PolicyService;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    protected PolicyService $policyService;

    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * Show Privacy Notice page
     */
    public function privacy()
    {
        $policy = $this->policyService->getPrivacyPolicy();
        return view('legal.privacy', compact('policy'));
    }

    /**
     * Show Terms & Conditions page
     */
    public function terms()
    {
        $terms = $this->policyService->getTermsAndConditions();
        return view('legal.terms', compact('terms'));
    }
}
