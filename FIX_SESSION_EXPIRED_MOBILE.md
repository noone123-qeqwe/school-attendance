# Fix for "Session Expired" Error in Mobile App

## Problem
When users open the Android app and try to create an account, they get a "Your session expired. Please refresh the page and try again." error even though they just started.

## Root Cause
The WebView in the Android app loads the registration page, but Laravel's CSRF token might not be properly initialized or cached, causing a 419 error when submitting the form.

## Solutions

### Solution 1: Use API Endpoints Instead of Web Forms (RECOMMENDED)
The Android app should use the API endpoints that don't require CSRF tokens:
- Change the app to call `/api/otp/send` instead of `/otp/register-send`
- Use JSON requests directly from native Android code instead of WebView forms

### Solution 2: Add CSRF Auto-Refresh Handler (IMPLEMENTED)
Add JavaScript code to detect 419 errors and automatically refresh the page to get a fresh CSRF token. The code will:
1. Detect when a 419 error occurs
2. Save form data to sessionStorage
3. Reload the page
4. Restore form data and retry

### Solution 3: Ensure Cookies Are Enabled in WebView
Make sure the Android WebView has cookies enabled for session persistence.

### Quick Test
Try these in order:
1. Close and reopen the Android app
2. Wait 2-3 seconds for the page to fully load
3. Try creating an account again

If it still fails, check the Laravel logs for the actual error.
