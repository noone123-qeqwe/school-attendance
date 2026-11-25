package com.school.attendance;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.SigningInfo;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.CancellationSignal;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyPermanentlyInvalidatedException;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import android.view.View;
import android.webkit.GeolocationPermissions;
import android.webkit.PermissionRequest;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.activity.OnBackPressedCallback;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.core.view.WindowInsetsControllerCompat;
import androidx.biometric.BiometricManager;
import androidx.biometric.BiometricPrompt;
import androidx.credentials.CreateCredentialResponse;
import androidx.credentials.CreatePublicKeyCredentialRequest;
import androidx.credentials.CreatePublicKeyCredentialResponse;
import androidx.credentials.CredentialManager;
import androidx.credentials.CredentialManagerCallback;
import androidx.credentials.GetCredentialRequest;
import androidx.credentials.GetCredentialResponse;
import androidx.credentials.GetPublicKeyCredentialOption;
import androidx.credentials.PublicKeyCredential;
import androidx.credentials.exceptions.CreateCredentialCancellationException;
import androidx.credentials.exceptions.CreateCredentialException;
import androidx.credentials.exceptions.GetCredentialCancellationException;
import androidx.credentials.exceptions.GetCredentialException;
import androidx.credentials.exceptions.GetCredentialProviderConfigurationException;
import androidx.credentials.exceptions.GetCredentialUnsupportedException;
import androidx.credentials.exceptions.NoCredentialException;
import androidx.webkit.JavaScriptReplyProxy;
import androidx.webkit.WebMessageCompat;
import androidx.webkit.WebSettingsCompat;
import androidx.webkit.WebViewCompat;
import androidx.webkit.WebViewFeature;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.math.BigInteger;
import java.nio.charset.StandardCharsets;
import java.security.KeyPair;
import java.security.KeyPairGenerator;
import java.security.KeyStore;
import java.security.MessageDigest;
import java.security.PrivateKey;
import java.security.SecureRandom;
import java.security.Signature;
import java.security.interfaces.ECPublicKey;
import java.security.spec.ECGenParameterSpec;
import java.security.spec.ECPoint;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Iterator;
import java.util.List;
import java.util.concurrent.Executor;

public class MainActivity extends AppCompatActivity {

    private static final String APP_URL = "https://school-attendance-oxk7.onrender.com";
    private static final Uri APP_ORIGIN = Uri.parse(APP_URL);
    private static final String SIGN_IN_URL = APP_URL + "/login";
    private static final String WEB_VIEW_ORIGIN_STATE = "attendance.webViewOrigin";
    private static final String KEYSTORE_PREFS = "app_passkey_keystore";
    private static final String KEYSTORE_ENTRIES_KEY = "entries_json";
    private static final String KEYSTORE_ALIAS_PREFIX = "att_passkey_";
    private static final int PERMISSION_REQUEST_CODE = 1001;
    private static final int CAMERA_PERMISSION_REQUEST_CODE = 1002;
    private static final int FILE_CHOOSER_REQUEST_CODE = 1003;

    private WebView webView;
    private ProgressBar progressBar;
    private View launchError;
    private ValueCallback<Uri[]> fileUploadCallback;
    private PermissionRequest pendingPermissionRequest;
    private CancellationSignal passkeyCancellation;
    private String passkeyRequestId;
    private BiometricPrompt activeBiometricPrompt;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        setContentView(R.layout.activity_main);
        enterImmersiveMode();

        webView = findViewById(R.id.webView);
        progressBar = findViewById(R.id.progressBar);
        launchError = findViewById(R.id.launchError);
        findViewById(R.id.retryLaunch).setOnClickListener(view -> openSignIn());

        // Adjust container padding for soft keyboard (IME) so inputs and modals are not covered
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(android.R.id.content), (view, insets) -> {
            int imeHeight = insets.getInsets(WindowInsetsCompat.Type.ime()).bottom;
            int navHeight = insets.getInsets(WindowInsetsCompat.Type.systemBars()).bottom;
            view.setPadding(0, 0, 0, Math.max(imeHeight, navHeight));
            return insets;
        });

        setupWebView();
        checkAndRequestPermissions();

        // Modern back button navigation handling
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    setEnabled(false);
                    getOnBackPressedDispatcher().onBackPressed();
                }
            }
        });

        // Saved history from an app pointed at a previous host must never drive startup.
        if (savedInstanceState == null
                || !APP_URL.equals(savedInstanceState.getString(WEB_VIEW_ORIGIN_STATE))
                || webView.restoreState(savedInstanceState) == null) {
            openSignIn();
        }
    }

    private void openSignIn() {
        launchError.setVisibility(View.GONE);
        webView.setVisibility(View.VISIBLE);
        webView.loadUrl(SIGN_IN_URL);
    }

    private static boolean isAttendanceUrl(Uri url) {
        return "https".equalsIgnoreCase(url.getScheme())
                && APP_ORIGIN.getHost().equalsIgnoreCase(url.getHost())
                && (url.getPort() == -1 || url.getPort() == 443);
    }

    private static boolean isAttendanceOriginUrl(@Nullable String currentUrl) {
        if (currentUrl == null) return false;
        Uri url = Uri.parse(currentUrl);
        return isAttendanceUrl(url);
    }

    private static boolean isAttendanceLoginUrl(@Nullable String currentUrl) {
        if (currentUrl == null) return false;
        Uri url = Uri.parse(currentUrl);
        // Guest QR scans also redirect here, with the attendance token in the query.
        return isAttendanceUrl(url) && "/login".equals(url.getEncodedPath());
    }

    private static boolean isAttendanceEntryRequest(WebResourceRequest request) {
        Uri url = request.getUrl();
        if (!request.isForMainFrame() || !"GET".equals(request.getMethod()) || !isAttendanceUrl(url)) {
            return false;
        }
        String path = url.getPath();
        return path == null || path.isEmpty() || "/".equals(path) || "/login".equals(path)
                || "/home".equals(path);
    }

    private void showLaunchError() {
        progressBar.setVisibility(View.GONE);
        webView.setVisibility(View.INVISIBLE);
        launchError.setVisibility(View.VISIBLE);
    }

    private void enterImmersiveMode() {
        WindowInsetsControllerCompat controller = WindowCompat.getInsetsController(
                getWindow(), getWindow().getDecorView());
        controller.setSystemBarsBehavior(WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
        controller.hide(WindowInsetsCompat.Type.systemBars());
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus) {
            enterImmersiveMode();
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    private void setupWebView() {
        // Prevent system overscroll effects from triggering reload or interfering with smooth scrolling
        webView.setOverScrollMode(View.OVER_SCROLL_NEVER);
        webView.setVerticalScrollBarEnabled(true);
        webView.setHorizontalScrollBarEnabled(false);
        webView.setScrollBarStyle(View.SCROLLBARS_INSIDE_OVERLAY);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setGeolocationEnabled(true);
        settings.setSupportZoom(true);
        settings.setBuiltInZoomControls(false);
        settings.setUseWideViewPort(true);
        // Keep the responsive page at its declared scale. Overview mode recalculates
        // the WebView scale whenever Android reports a transient system-bar inset.
        settings.setLoadWithOverviewMode(false);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setSupportMultipleWindows(false);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);

        // WebView disables WebAuthn by default. Enable its Credential Manager
        // integration so the login page can show Android's biometric/passkey UI.
        if (WebViewFeature.isFeatureSupported(WebViewFeature.WEB_AUTHENTICATION)) {
            WebSettingsCompat.setWebAuthenticationSupport(
                    settings,
                    WebSettingsCompat.WEB_AUTHENTICATION_SUPPORT_FOR_APP
            );
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P
                && WebViewFeature.isFeatureSupported(WebViewFeature.WEB_MESSAGE_LISTENER)) {
            WebViewCompat.addWebMessageListener(webView, "AttendancePasskey",
                    Collections.singleton(APP_URL), this::onPasskeyMessage);
        }

        // Native Device Binding Bridge: exposes stable hardware/app installation UUID
        webView.addJavascriptInterface(new AndroidDeviceBridge(this), "AndroidDeviceBridge");

        // Customize User Agent so server knows it's the official Android app wrapper
        String defaultUa = settings.getUserAgentString();
        settings.setUserAgentString(defaultUa + " SmartAttendanceApp/1.0 (Android Native)");

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                cancelPasskeyRequest();
                launchError.setVisibility(View.GONE);
                view.setVisibility(View.VISIBLE);
                progressBar.setVisibility(View.VISIBLE);
                super.onPageStarted(view, url, favicon);
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                progressBar.setVisibility(View.GONE);
                super.onPageFinished(view, url);
                if (launchError.getVisibility() == View.VISIBLE) {
                    return;
                }

                // Auto-detect Render cold-start / waking-up screen and automatically retry until live
                String pageTitle = view != null ? view.getTitle() : null;
                if (pageTitle != null && (pageTitle.equalsIgnoreCase("Application Loading") 
                        || pageTitle.toLowerCase().contains("render") 
                        || pageTitle.toLowerCase().contains("waking up"))) {
                    view.postDelayed(() -> {
                        if (url.equals(view.getUrl()) && launchError.getVisibility() != View.VISIBLE) {
                            view.reload();
                        }
                    }, 4000);
                }
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                if (request.isForMainFrame()) {
                    progressBar.setVisibility(View.GONE);
                }
                if (isAttendanceEntryRequest(request)) {
                    showLaunchError();
                }
                super.onReceivedError(view, request, error);
            }

            @Override
            public void onReceivedHttpError(WebView view, WebResourceRequest request,
                                            WebResourceResponse response) {
                if (isAttendanceEntryRequest(request)
                        && (response.getStatusCode() == 404 || response.getStatusCode() >= 500)) {
                    showLaunchError();
                }
                super.onReceivedHttpError(view, request, response);
            }

            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                if (url.startsWith("tel:") || url.startsWith("mailto:") || url.startsWith("sms:")) {
                    Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                    startActivity(intent);
                    return true;
                }
                return false;
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
                if (newProgress == 100 || launchError.getVisibility() == View.VISIBLE) {
                    progressBar.setVisibility(View.GONE);
                } else {
                    progressBar.setVisibility(View.VISIBLE);
                }
                super.onProgressChanged(view, newProgress);
            }

            // Grants camera permission for HTML5 QR Code scanning
            @Override
            public void onPermissionRequest(final PermissionRequest request) {
                runOnUiThread(() -> {
                    String[] resources = request.getResources();
                    for (String r : resources) {
                        if (PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(r)) {
                            // Check if Android camera permission is granted
                            if (ContextCompat.checkSelfPermission(MainActivity.this, Manifest.permission.CAMERA)
                                    == PackageManager.PERMISSION_GRANTED) {
                                // Permission already granted, allow WebView to access camera
                                request.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
                            } else {
                                // Permission not granted, request it from user
                                pendingPermissionRequest = request;
                                ActivityCompat.requestPermissions(MainActivity.this,
                                        new String[]{Manifest.permission.CAMERA}, CAMERA_PERMISSION_REQUEST_CODE);
                            }
                            return;
                        }
                    }
                    // For other resources (audio, protected media, etc.), grant directly
                    request.grant(resources);
                });
            }

            // Grants Geolocation for GPS attendance check-in
            @Override
            public void onGeolocationPermissionsShowPrompt(String origin, GeolocationPermissions.Callback callback) {
                if (ContextCompat.checkSelfPermission(MainActivity.this, Manifest.permission.ACCESS_FINE_LOCATION)
                        == PackageManager.PERMISSION_GRANTED) {
                    callback.invoke(origin, true, false);
                } else {
                    ActivityCompat.requestPermissions(MainActivity.this,
                            new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION},
                            PERMISSION_REQUEST_CODE);
                    callback.invoke(origin, true, false);
                }
            }

            // Handles file uploads (profile picture, excuse letter attachments)
            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback,
                                             FileChooserParams fileChooserParams) {
                if (fileUploadCallback != null) {
                    fileUploadCallback.onReceiveValue(null);
                }
                fileUploadCallback = filePathCallback;

                Intent intent = fileChooserParams.createIntent();
                try {
                    startActivityForResult(intent, FILE_CHOOSER_REQUEST_CODE);
                } catch (Exception e) {
                    fileUploadCallback = null;
                    Toast.makeText(MainActivity.this, "Cannot open file chooser", Toast.LENGTH_SHORT).show();
                    return false;
                }
                return true;
            }
        });
    }

    private void checkAndRequestPermissions() {
        List<String> permissions = new ArrayList<>();
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            permissions.add(Manifest.permission.CAMERA);
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            permissions.add(Manifest.permission.ACCESS_FINE_LOCATION);
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.POST_NOTIFICATIONS);
            }
        }

        if (!permissions.isEmpty()) {
            ActivityCompat.requestPermissions(this, permissions.toArray(new String[0]), PERMISSION_REQUEST_CODE);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        
        if (requestCode == CAMERA_PERMISSION_REQUEST_CODE) {
            // Handle camera permission result for WebView
            if (grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                // Permission granted, now grant it to WebView
                if (pendingPermissionRequest != null) {
                    pendingPermissionRequest.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
                    pendingPermissionRequest = null;
                    Toast.makeText(this, "Camera access granted", Toast.LENGTH_SHORT).show();
                }
            } else {
                // Permission denied
                if (pendingPermissionRequest != null) {
                    pendingPermissionRequest.deny();
                    pendingPermissionRequest = null;
                    Toast.makeText(this, "Camera permission is required to scan QR codes", Toast.LENGTH_LONG).show();
                }
            }
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        if (requestCode == FILE_CHOOSER_REQUEST_CODE) {
            if (fileUploadCallback != null) {
                Uri[] results = null;
                if (resultCode == Activity.RESULT_OK && data != null) {
                    if (data.getData() != null) {
                        results = new Uri[]{data.getData()};
                    } else if (data.getClipData() != null) {
                        int count = data.getClipData().getItemCount();
                        results = new Uri[count];
                        for (int i = 0; i < count; i++) {
                            results[i] = data.getClipData().getItemAt(i).getUri();
                        }
                    }
                }
                fileUploadCallback.onReceiveValue(results);
                fileUploadCallback = null;
            }
        } else {
            super.onActivityResult(requestCode, resultCode, data);
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        // Keep webView state active without reloads when status bar or notification shade is opened
    }

    @Override
    protected void onResume() {
        super.onResume();
        enterImmersiveMode();
    }

    @Override
    protected void onDestroy() {
        cancelPasskeyRequest();
        super.onDestroy();
    }

    private void onPasskeyMessage(@NonNull WebView view, @NonNull WebMessageCompat message,
                                  @NonNull Uri sourceOrigin, boolean isMainFrame,
                                  @NonNull JavaScriptReplyProxy reply) {
        if (!isMainFrame || !isAttendanceUrl(sourceOrigin) || !isAttendanceOriginUrl(view.getUrl())) {
            return;
        }

        JSONObject request;
        try {
            String data = message.getData();
            if (data == null) return;
            request = new JSONObject(data);
        } catch (JSONException | IllegalStateException invalidMessage) {
            return;
        }

        Object requestId = request.opt("id");
        if (!(requestId instanceof String)) return;
        String id = (String) requestId;
        if (id.isEmpty() || id.length() > 128) return;

        String type = request.optString("type");
        if ("cancel".equals(type) && id.equals(passkeyRequestId)) {
            cancelPasskeyRequest();
            sendPasskeyReply(reply, id, null, "AbortError");
            return;
        }
        if ("capabilities".equals(type)) {
            try {
                JSONObject caps = new JSONObject()
                        .put("strongBiometric", canUseStrongBiometricPrompt())
                        .put("hasLocalKeystorePasskey", findLatestKeystoreEntry(APP_ORIGIN.getHost()) != null);
                JSONObject payload = new JSONObject().put("id", id).put("capabilities", caps);
                reply.postMessage(payload.toString());
            } catch (JSONException ignored) {
            }
            return;
        }

        if ("create".equals(type)) {
            handlePasskeyCreate(reply, id, request.optJSONObject("publicKey"));
            return;
        }
        if ("get".equals(type)) {
            handlePasskeyGet(reply, id, request.optJSONObject("publicKey"));
        }
    }

    private void handlePasskeyCreate(@NonNull JavaScriptReplyProxy reply, @NonNull String id,
                                     @Nullable JSONObject options) {
        String rpHost = APP_ORIGIN.getHost();
        String optionRpId = "";
        if (options != null) {
            JSONObject rpObj = options.optJSONObject("rp");
            optionRpId = rpObj != null ? rpObj.optString("id", "") : options.optString("rpId", "");
        }
        if (options == null || rpHost == null || !rpHost.equalsIgnoreCase(optionRpId)
                || options.optString("challenge", "").isEmpty()) {
            sendPasskeyReply(reply, id, null, "UnknownError");
            return;
        }
        if (passkeyCancellation != null) {
            sendPasskeyReply(reply, id, null, "AbortError");
            return;
        }

        JSONObject normalized = normalizeCreateOptionsForAndroid(options, rpHost);
        CancellationSignal cancellation = new CancellationSignal();
        passkeyCancellation = cancellation;
        passkeyRequestId = id;

        if (canUseBiometricPrompt()) {
            createWithAndroidKeystoreBiometric(cancellation, reply, id, normalized, rpHost);
            return;
        }

        try {
            CreatePublicKeyCredentialRequest createRequest = new CreatePublicKeyCredentialRequest(normalized.toString());
            CredentialManager.create(this).createCredentialAsync(this, createRequest, cancellation,
                    ContextCompat.getMainExecutor(this),
                    new CredentialManagerCallback<CreateCredentialResponse, CreateCredentialException>() {
                        @Override
                        public void onResult(@NonNull CreateCredentialResponse response) {
                            if (passkeyCancellation != cancellation) return;
                            if (response instanceof CreatePublicKeyCredentialResponse) {
                                String rawJson = ((CreatePublicKeyCredentialResponse) response).getRegistrationResponseJson();
                                try {
                                    JSONObject credentialJson = new JSONObject(rawJson);
                                    finishPasskeyRequest(cancellation, reply, id, credentialJson, null);
                                    return;
                                } catch (JSONException ignored) {
                                }
                            }
                            if (canUseBiometricPrompt()) {
                                createWithAndroidKeystoreBiometric(cancellation, reply, id, normalized, rpHost);
                            } else {
                                finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
                            }
                        }

                        @Override
                        public void onError(@NonNull CreateCredentialException error) {
                            if (passkeyCancellation != cancellation) return;
                            if (error instanceof CreateCredentialCancellationException) {
                                finishPasskeyRequest(cancellation, reply, id, null, "NotAllowedError");
                                return;
                            }
                            if (canUseBiometricPrompt()) {
                                createWithAndroidKeystoreBiometric(cancellation, reply, id, normalized, rpHost);
                                return;
                            }
                            finishPasskeyRequest(cancellation, reply, id, null, "NotSupportedError");
                        }
                    });
        } catch (RuntimeException ex) {
            if (passkeyCancellation != cancellation) return;
            if (canUseBiometricPrompt()) {
                createWithAndroidKeystoreBiometric(cancellation, reply, id, normalized, rpHost);
            } else {
                finishPasskeyRequest(cancellation, reply, id, null,
                        ex instanceof UnsupportedOperationException ? "NotSupportedError" : "UnknownError");
            }
        }
    }

    private void handlePasskeyGet(@NonNull JavaScriptReplyProxy reply, @NonNull String id,
                                  @Nullable JSONObject options) {
        String rpHost = APP_ORIGIN.getHost();
        if (options == null || rpHost == null || !rpHost.equalsIgnoreCase(options.optString("rpId"))
                || !"required".equals(options.optString("userVerification"))) {
            sendPasskeyReply(reply, id, null, "UnknownError");
            return;
        }
        if (passkeyCancellation != null) {
            sendPasskeyReply(reply, id, null, "AbortError");
            return;
        }

        CancellationSignal cancellation = new CancellationSignal();
        passkeyCancellation = cancellation;
        passkeyRequestId = id;

        JSONArray allowCredentials = options.optJSONArray("allowCredentials");
        boolean emptyAllowList = allowCredentials == null || allowCredentials.length() == 0;
        JSONObject matchedKeystore = findMatchingKeystoreEntry(rpHost, allowCredentials);
        if (matchedKeystore == null && emptyAllowList) {
            matchedKeystore = findLatestKeystoreEntry(rpHost);
        }
        if (matchedKeystore != null && canUseBiometricPrompt()) {
            getWithAndroidKeystoreBiometric(cancellation, reply, id, options, rpHost, matchedKeystore);
            return;
        }

        try {
            GetCredentialRequest credentialRequest = new GetCredentialRequest.Builder()
                    .addCredentialOption(new GetPublicKeyCredentialOption(options.toString()))
                    .setPreferImmediatelyAvailableCredentials(true)
                    .build();
            CredentialManager.create(this).getCredentialAsync(this, credentialRequest, cancellation,
                    ContextCompat.getMainExecutor(this),
                    new CredentialManagerCallback<GetCredentialResponse, GetCredentialException>() {
                        @Override
                        public void onResult(@NonNull GetCredentialResponse response) {
                            if (passkeyCancellation != cancellation) return;
                            if (response.getCredential() instanceof PublicKeyCredential) {
                                PublicKeyCredential credential = (PublicKeyCredential) response.getCredential();
                                try {
                                    JSONObject assertion = new JSONObject(credential.getAuthenticationResponseJson());
                                    finishPasskeyRequest(cancellation, reply, id, assertion, null);
                                    return;
                                } catch (JSONException invalidResponse) {
                                    // Credential providers must return a WebAuthn assertion as JSON.
                                }
                            }
                            finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
                        }

                        @Override
                        public void onError(@NonNull GetCredentialException error) {
                            if (passkeyCancellation != cancellation) return;
                            if (error instanceof GetCredentialCancellationException) {
                                finishPasskeyRequest(cancellation, reply, id, null, "NotAllowedError");
                                return;
                            }
                            JSONObject fallbackEntry = emptyAllowList ? findLatestKeystoreEntry(rpHost) : null;
                            if (fallbackEntry != null && canUseBiometricPrompt()) {
                                getWithAndroidKeystoreBiometric(cancellation, reply, id, options, rpHost, fallbackEntry);
                                return;
                            }

                            String name = "UnknownError";
                            if (error instanceof NoCredentialException) {
                                name = "NotFoundError";
                            } else if (error instanceof GetCredentialUnsupportedException
                                    || error instanceof GetCredentialProviderConfigurationException) {
                                name = "NotSupportedError";
                            }
                            finishPasskeyRequest(cancellation, reply, id, null, name);
                        }
                    });
        } catch (RuntimeException ex) {
            if (passkeyCancellation != cancellation) return;
            JSONObject fallbackEntry = emptyAllowList ? findLatestKeystoreEntry(rpHost) : null;
            if (fallbackEntry != null && canUseBiometricPrompt()) {
                getWithAndroidKeystoreBiometric(cancellation, reply, id, options, rpHost, fallbackEntry);
            } else {
                finishPasskeyRequest(cancellation, reply, id, null,
                        ex instanceof UnsupportedOperationException ? "NotSupportedError" : "UnknownError");
            }
        }
    }

    private JSONObject normalizeCreateOptionsForAndroid(JSONObject source, String rpHost) {
        try {
            JSONObject copy = new JSONObject(source.toString());
            JSONObject rp = copy.optJSONObject("rp");
            if (rp == null) rp = new JSONObject();
            rp.put("id", rpHost);
            if (!rp.has("name")) rp.put("name", "School Attendance");
            copy.put("rp", rp);

            JSONObject selection = copy.optJSONObject("authenticatorSelection");
            if (selection == null) selection = new JSONObject();
            selection.put("authenticatorAttachment", "platform");
            selection.put("userVerification", "required");
            if (!selection.has("residentKey")) selection.put("residentKey", "required");
            copy.put("authenticatorSelection", selection);

            if (!copy.has("pubKeyCredParams")) {
                JSONArray params = new JSONArray();
                params.put(new JSONObject().put("type", "public-key").put("alg", -7));
                params.put(new JSONObject().put("type", "public-key").put("alg", -257));
                copy.put("pubKeyCredParams", params);
            }
            if (!copy.has("attestation")) copy.put("attestation", "none");
            if (!copy.has("excludeCredentials")) copy.put("excludeCredentials", new JSONArray());
            return copy;
        } catch (JSONException ignored) {
            return source;
        }
    }

    private boolean canUseStrongBiometricPrompt() {
        try {
            return BiometricManager.from(this)
                    .canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) == BiometricManager.BIOMETRIC_SUCCESS;
        } catch (Exception ignored) {
            return false;
        }
    }

    /**
     * True when the device can show a fingerprint prompt at all. Many phones rate their
     * fingerprint sensor as "weak" (Class 2) and canUseStrongBiometricPrompt() is false for
     * them, which previously made the app skip the fingerprint prompt entirely.
     */
    private boolean canUseBiometricPrompt() {
        try {
            BiometricManager manager = BiometricManager.from(this);
            int strong = manager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG);
            int weak = manager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_WEAK);
            if (strong == BiometricManager.BIOMETRIC_SUCCESS || weak == BiometricManager.BIOMETRIC_SUCCESS) {
                return true;
            }
            // Only refuse when we know there is no usable fingerprint. Odd/unknown statuses
            // (e.g. STATUS_UNKNOWN on Android 9-10, UNSUPPORTED, security update pending)
            // are let through so BiometricPrompt itself decides and reports a real error.
            return !isDefinitelyNoBiometric(strong) || !isDefinitelyNoBiometric(weak);
        } catch (Exception ignored) {
            return false;
        }
    }

    private static boolean isDefinitelyNoBiometric(int status) {
        return status == BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE
                || status == BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED
                || status == BiometricManager.BIOMETRIC_ERROR_HW_UNAVAILABLE;
    }
    private void createWithAndroidKeystoreBiometric(@NonNull CancellationSignal cancellation,
                                                     @NonNull JavaScriptReplyProxy reply,
                                                     @NonNull String id,
                                                     @NonNull JSONObject options,
                                                     @NonNull String rpId) {
        runOnUiThread(() -> {
            if (passkeyCancellation != cancellation || cancellation.isCanceled()) return;
            String alias = null;
            try {
                byte[] credIdBytes = new byte[32];
                new SecureRandom().nextBytes(credIdBytes);
                String credIdB64 = base64UrlEncode(credIdBytes);
                alias = KEYSTORE_ALIAS_PREFIX + credIdB64;

                // Strong sensors bind the key to the fingerprint (crypto-backed). Weak (Class 2)
                // sensors cannot unlock crypto-bound keys, so the key is gated by an app-level
                // BiometricPrompt instead.
                final boolean strongKey = canUseStrongBiometricPrompt();
                KeyGenParameterSpec.Builder specBuilder = new KeyGenParameterSpec.Builder(
                        alias, KeyProperties.PURPOSE_SIGN)
                        .setAlgorithmParameterSpec(new ECGenParameterSpec("secp256r1"))
                        .setDigests(KeyProperties.DIGEST_SHA256);
                if (strongKey) {
                    specBuilder.setUserAuthenticationRequired(true)
                            .setInvalidatedByBiometricEnrollment(true);
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                        specBuilder.setUserAuthenticationParameters(0, KeyProperties.AUTH_BIOMETRIC_STRONG);
                    } else {
                        @SuppressWarnings("deprecation")
                        KeyGenParameterSpec.Builder ignored = specBuilder.setUserAuthenticationValidityDurationSeconds(-1);
                    }
                } else {
                    specBuilder.setUserAuthenticationRequired(false);
                }

                KeyPairGenerator kpg = KeyPairGenerator.getInstance(KeyProperties.KEY_ALGORITHM_EC, "AndroidKeyStore");
                kpg.initialize(specBuilder.build());
                KeyPair keyPair = kpg.generateKeyPair();

                ECPublicKey ecPub = (ECPublicKey) keyPair.getPublic();
                ECPoint point = ecPub.getW();
                byte[] xBytes = toFixed32UnsignedBytes(point.getAffineX());
                byte[] yBytes = toFixed32UnsignedBytes(point.getAffineY());

                Signature signature = Signature.getInstance("SHA256withECDSA");
                signature.initSign(keyPair.getPrivate());

                String challenge = options.optString("challenge", "");
                JSONObject userObj = options.optJSONObject("user");
                String userHandle = userObj != null ? userObj.optString("id", "") : "";
                String finalAlias = alias;

                Executor executor = ContextCompat.getMainExecutor(this);
                BiometricPrompt prompt = new BiometricPrompt(this, executor, new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                        activeBiometricPrompt = null;
                        if (passkeyCancellation != cancellation) {
                            deleteKeystoreAlias(finalAlias);
                            return;
                        }
                        try {
                            byte[] coseKey = buildCoseEc2PublicKey(xBytes, yBytes);
                            byte[] authData = buildAttestedAuthData(rpId, credIdBytes, coseKey);
                            byte[] attestationObject = buildAttestationObject(authData);
                            byte[] clientDataBytes = buildClientDataJson("webauthn.create", challenge);

                            saveKeystoreEntry(credIdB64, finalAlias, rpId, userHandle, 1, strongKey);

                            JSONArray transports = new JSONArray().put("internal");
                            JSONObject responseObj = new JSONObject()
                                    .put("clientDataJSON", base64UrlEncode(clientDataBytes))
                                    .put("attestationObject", base64UrlEncode(attestationObject))
                                    .put("transports", transports);
                            JSONObject credentialPayload = new JSONObject()
                                    .put("id", credIdB64)
                                    .put("rawId", credIdB64)
                                    .put("type", "public-key")
                                    .put("response", responseObj);

                            finishPasskeyRequest(cancellation, reply, id, credentialPayload, null);
                        } catch (Exception ex) {
                            deleteKeystoreAlias(finalAlias);
                            finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
                        }
                    }

                    @Override
                    public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                        activeBiometricPrompt = null;
                        deleteKeystoreAlias(finalAlias);
                        boolean cancelled = errorCode == BiometricPrompt.ERROR_USER_CANCELED
                                || errorCode == BiometricPrompt.ERROR_NEGATIVE_BUTTON
                                || errorCode == BiometricPrompt.ERROR_CANCELED;
                        finishPasskeyRequest(cancellation, reply, id, null, cancelled ? "NotAllowedError" : "UnknownError");
                    }

                    @Override
                    public void onAuthenticationFailed() {
                        // BiometricPrompt continues waiting for a valid fingerprint scan.
                    }
                });

                activeBiometricPrompt = prompt;
                BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                        .setTitle("Enable Fingerprint Sign-In")
                        .setSubtitle("Touch the fingerprint sensor to register this device")
                        .setAllowedAuthenticators(strongKey
                                ? BiometricManager.Authenticators.BIOMETRIC_STRONG
                                : BiometricManager.Authenticators.BIOMETRIC_WEAK)
                        .setNegativeButtonText("Cancel")
                        .build();
                if (strongKey) {
                    prompt.authenticate(promptInfo, new BiometricPrompt.CryptoObject(signature));
                } else {
                    prompt.authenticate(promptInfo);
                }
            } catch (Exception ex) {
                activeBiometricPrompt = null;
                if (alias != null) deleteKeystoreAlias(alias);
                finishPasskeyRequest(cancellation, reply, id, null, "NotSupportedError");
            }
        });
    }

    private void getWithAndroidKeystoreBiometric(@NonNull CancellationSignal cancellation,
                                                  @NonNull JavaScriptReplyProxy reply,
                                                  @NonNull String id,
                                                  @NonNull JSONObject options,
                                                  @NonNull String rpId,
                                                  @NonNull JSONObject entry) {
        runOnUiThread(() -> {
            if (passkeyCancellation != cancellation || cancellation.isCanceled()) return;
            String credIdB64 = entry.optString("credentialId", "");
            String alias = entry.optString("alias", "");
            String userHandle = entry.optString("userHandle", "");
            final boolean strongEntry = entry.optBoolean("strong", true);
            long nextSignCount = Math.max(1L, entry.optLong("signCount", 1L)) + 1L;
            String challenge = options.optString("challenge", "");

            try {
                KeyStore keyStore = KeyStore.getInstance("AndroidKeyStore");
                keyStore.load(null);
                PrivateKey privateKey = (PrivateKey) keyStore.getKey(alias, null);
                if (privateKey == null) {
                    removeKeystoreEntry(credIdB64);
                    finishPasskeyRequest(cancellation, reply, id, null, "NotFoundError");
                    return;
                }

                byte[] clientDataBytes = buildClientDataJson("webauthn.get", challenge);
                byte[] authenticatorData = buildAssertionAuthData(rpId, nextSignCount);
                byte[] clientDataHash = MessageDigest.getInstance("SHA-256").digest(clientDataBytes);
                ByteArrayOutputStream signedOut = new ByteArrayOutputStream(authenticatorData.length + clientDataHash.length);
                signedOut.write(authenticatorData);
                signedOut.write(clientDataHash);
                byte[] signedData = signedOut.toByteArray();

                Signature signature = Signature.getInstance("SHA256withECDSA");
                try {
                    signature.initSign(privateKey);
                } catch (KeyPermanentlyInvalidatedException invalidated) {
                    deleteKeystoreAlias(alias);
                    removeKeystoreEntry(credIdB64);
                    finishPasskeyRequest(cancellation, reply, id, null, "NotFoundError");
                    return;
                }

                Executor executor = ContextCompat.getMainExecutor(this);
                BiometricPrompt prompt = new BiometricPrompt(this, executor, new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                        activeBiometricPrompt = null;
                        if (passkeyCancellation != cancellation) return;
                        try {
                            BiometricPrompt.CryptoObject crypto = result.getCryptoObject();
                            Signature authedSig = crypto != null ? crypto.getSignature() : (strongEntry ? null : signature);
                            if (authedSig == null) {
                                finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
                                return;
                            }
                            authedSig.update(signedData);
                            byte[] sigBytes = authedSig.sign();

                            saveKeystoreEntry(credIdB64, alias, rpId, userHandle, nextSignCount);

                            JSONObject responseObj = new JSONObject()
                                    .put("clientDataJSON", base64UrlEncode(clientDataBytes))
                                    .put("authenticatorData", base64UrlEncode(authenticatorData))
                                    .put("signature", base64UrlEncode(sigBytes));
                            if (!userHandle.isEmpty()) {
                                responseObj.put("userHandle", userHandle);
                            }
                            JSONObject assertion = new JSONObject()
                                    .put("id", credIdB64)
                                    .put("rawId", credIdB64)
                                    .put("type", "public-key")
                                    .put("response", responseObj);

                            finishPasskeyRequest(cancellation, reply, id, assertion, null);
                        } catch (Exception ex) {
                            finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
                        }
                    }

                    @Override
                    public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                        activeBiometricPrompt = null;
                        boolean cancelled = errorCode == BiometricPrompt.ERROR_USER_CANCELED
                                || errorCode == BiometricPrompt.ERROR_NEGATIVE_BUTTON
                                || errorCode == BiometricPrompt.ERROR_CANCELED;
                        finishPasskeyRequest(cancellation, reply, id, null, cancelled ? "NotAllowedError" : "UnknownError");
                    }

                    @Override
                    public void onAuthenticationFailed() {
                        // BiometricPrompt continues listening for a matching fingerprint.
                    }
                });

                activeBiometricPrompt = prompt;
                BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                        .setTitle("Verify Your Fingerprint")
                        .setSubtitle("Touch the fingerprint sensor to sign in")
                        .setAllowedAuthenticators(strongEntry
                                ? BiometricManager.Authenticators.BIOMETRIC_STRONG
                                : BiometricManager.Authenticators.BIOMETRIC_WEAK)
                        .setNegativeButtonText("Cancel")
                        .build();
                if (strongEntry) {
                    prompt.authenticate(promptInfo, new BiometricPrompt.CryptoObject(signature));
                } else {
                    prompt.authenticate(promptInfo);
                }
            } catch (Exception ex) {
                activeBiometricPrompt = null;
                finishPasskeyRequest(cancellation, reply, id, null, "UnknownError");
            }
        });
    }

    private JSONObject readKeystoreEntries() {
        try {
            SharedPreferences prefs = getSharedPreferences(KEYSTORE_PREFS, MODE_PRIVATE);
            String raw = prefs.getString(KEYSTORE_ENTRIES_KEY, "{}");
            return new JSONObject(raw != null ? raw : "{}");
        } catch (JSONException ignored) {
            return new JSONObject();
        }
    }

    private void writeKeystoreEntries(JSONObject entries) {
        SharedPreferences prefs = getSharedPreferences(KEYSTORE_PREFS, MODE_PRIVATE);
        prefs.edit().putString(KEYSTORE_ENTRIES_KEY, entries.toString()).apply();
    }

    private void saveKeystoreEntry(String credIdB64, String alias, String rpId, String userHandle, long signCount) {
        JSONObject existing = readKeystoreEntries().optJSONObject(credIdB64);
        saveKeystoreEntry(credIdB64, alias, rpId, userHandle, signCount,
                existing == null || existing.optBoolean("strong", true));
    }

    private void saveKeystoreEntry(String credIdB64, String alias, String rpId, String userHandle,
                                   long signCount, boolean strong) {
        try {
            JSONObject entries = readKeystoreEntries();
            JSONObject existing = entries.optJSONObject(credIdB64);
            long createdAt = existing != null ? existing.optLong("createdAt", System.currentTimeMillis()) : System.currentTimeMillis();
            JSONObject entry = new JSONObject()
                    .put("credentialId", credIdB64)
                    .put("alias", alias)
                    .put("rpId", rpId)
                    .put("userHandle", userHandle)
                    .put("signCount", signCount)
                    .put("strong", strong)
                    .put("createdAt", createdAt);
            entries.put(credIdB64, entry);
            writeKeystoreEntries(entries);
        } catch (JSONException ignored) {
        }
    }

    private void removeKeystoreEntry(String credIdB64) {
        JSONObject entries = readKeystoreEntries();
        entries.remove(credIdB64);
        writeKeystoreEntries(entries);
    }

    private boolean keystoreAliasExists(String alias) {
        try {
            KeyStore ks = KeyStore.getInstance("AndroidKeyStore");
            ks.load(null);
            return ks.containsAlias(alias);
        } catch (Exception ignored) {
            return false;
        }
    }

    private void deleteKeystoreAlias(String alias) {
        try {
            KeyStore ks = KeyStore.getInstance("AndroidKeyStore");
            ks.load(null);
            if (ks.containsAlias(alias)) ks.deleteEntry(alias);
        } catch (Exception ignored) {
        }
    }

    @Nullable
    private JSONObject findMatchingKeystoreEntry(String rpId, @Nullable JSONArray allowCredentials) {
        if (allowCredentials == null || allowCredentials.length() == 0) return null;
        JSONObject entries = readKeystoreEntries();
        boolean dirty = false;
        JSONObject match = null;

        for (int i = 0; i < allowCredentials.length(); i++) {
            JSONObject descriptor = allowCredentials.optJSONObject(i);
            if (descriptor == null) continue;
            String normalizedId = normalizeBase64Url(descriptor.optString("id", ""));
            if (normalizedId.isEmpty()) continue;
            JSONObject entry = entries.optJSONObject(normalizedId);
            if (entry == null) continue;
            if (!rpId.equalsIgnoreCase(entry.optString("rpId", ""))) continue;
            if (!keystoreAliasExists(entry.optString("alias", ""))) {
                entries.remove(normalizedId);
                dirty = true;
                continue;
            }
            match = entry;
            break;
        }
        if (dirty) writeKeystoreEntries(entries);
        return match;
    }

    @Nullable
    private JSONObject findLatestKeystoreEntry(String rpId) {
        JSONObject entries = readKeystoreEntries();
        Iterator<String> keys = entries.keys();
        JSONObject best = null;
        long bestCreated = -1L;
        List<String> stale = new ArrayList<>();

        while (keys.hasNext()) {
            String key = keys.next();
            JSONObject entry = entries.optJSONObject(key);
            if (entry == null || !rpId.equalsIgnoreCase(entry.optString("rpId", ""))) continue;
            if (!keystoreAliasExists(entry.optString("alias", ""))) {
                stale.add(key);
                continue;
            }
            long createdAt = entry.optLong("createdAt", 0L);
            if (createdAt >= bestCreated) {
                bestCreated = createdAt;
                best = entry;
            }
        }
        if (!stale.isEmpty()) {
            for (String s : stale) entries.remove(s);
            writeKeystoreEntries(entries);
        }
        return best;
    }

    private byte[] buildClientDataJson(String type, String challenge) throws JSONException {
        JSONObject clientData = new JSONObject()
                .put("type", type)
                .put("challenge", challenge)
                .put("origin", getWebAuthnOrigin())
                .put("crossOrigin", false);
        return clientData.toString().getBytes(StandardCharsets.UTF_8);
    }

    private String getWebAuthnOrigin() {
        byte[] cert = getSigningCertificateBytes();
        if (cert != null) {
            try {
                byte[] digest = MessageDigest.getInstance("SHA-256").digest(cert);
                return "android:apk-key-hash:" + base64UrlEncode(digest);
            } catch (Exception ignored) {
            }
        }
        return APP_URL;
    }

    @Nullable
    private byte[] getSigningCertificateBytes() {
        try {
            PackageManager pm = getPackageManager();
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                PackageInfo info = pm.getPackageInfo(getPackageName(), PackageManager.GET_SIGNING_CERTIFICATES);
                SigningInfo signingInfo = info.signingInfo;
                if (signingInfo != null) {
                    android.content.pm.Signature[] sigs = signingInfo.hasMultipleSigners()
                            ? signingInfo.getApkContentsSigners()
                            : signingInfo.getSigningCertificateHistory();
                    if (sigs != null && sigs.length > 0) {
                        return sigs[0].toByteArray();
                    }
                }
            } else {
                @SuppressWarnings("deprecation")
                PackageInfo info = pm.getPackageInfo(getPackageName(), PackageManager.GET_SIGNATURES);
                if (info.signatures != null && info.signatures.length > 0) {
                    return info.signatures[0].toByteArray();
                }
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    private static byte[] toFixed32UnsignedBytes(BigInteger value) {
        byte[] raw = value.toByteArray();
        byte[] out = new byte[32];
        if (raw.length == 32) {
            return raw;
        } else if (raw.length > 32) {
            System.arraycopy(raw, raw.length - 32, out, 0, 32);
        } else {
            System.arraycopy(raw, 0, out, 32 - raw.length, raw.length);
        }
        return out;
    }

    private static byte[] buildCoseEc2PublicKey(byte[] x32, byte[] y32) {
        ByteArrayOutputStream out = new ByteArrayOutputStream(80);
        // Map(5): {1: 2, 3: -7, -1: 1, -2: bstr(32), -3: bstr(32)}
        out.write(0xa5);
        out.write(0x01); out.write(0x02); // kty: EC2
        out.write(0x03); out.write(0x26); // alg: ES256 (-7 -> 0x20 | 6)
        out.write(0x20); out.write(0x01); // crv: P-256 (-1 -> 0x20 | 0)
        out.write(0x21); writeCborBytes(out, x32); // x (-2 -> 0x20 | 1)
        out.write(0x22); writeCborBytes(out, y32); // y (-3 -> 0x20 | 2)
        return out.toByteArray();
    }

    private static byte[] buildAttestedAuthData(String rpId, byte[] credId, byte[] cosePublicKey) throws Exception {
        byte[] rpIdHash = MessageDigest.getInstance("SHA-256").digest(rpId.getBytes(StandardCharsets.UTF_8));
        ByteArrayOutputStream out = new ByteArrayOutputStream(64 + credId.length + cosePublicKey.length);
        out.write(rpIdHash);
        out.write(0x45); // UP (0x01) | UV (0x04) | AT (0x40)
        out.write(0x00); out.write(0x00); out.write(0x00); out.write(0x01); // signCount = 1
        out.write(new byte[16]); // AAGUID (zeroes for software/Keystore)
        out.write((credId.length >> 8) & 0xff);
        out.write(credId.length & 0xff);
        out.write(credId);
        out.write(cosePublicKey);
        return out.toByteArray();
    }

    private static byte[] buildAssertionAuthData(String rpId, long signCount) throws Exception {
        byte[] rpIdHash = MessageDigest.getInstance("SHA-256").digest(rpId.getBytes(StandardCharsets.UTF_8));
        ByteArrayOutputStream out = new ByteArrayOutputStream(37);
        out.write(rpIdHash);
        out.write(0x05); // UP (0x01) | UV (0x04)
        out.write((int) ((signCount >>> 24) & 0xff));
        out.write((int) ((signCount >>> 16) & 0xff));
        out.write((int) ((signCount >>> 8) & 0xff));
        out.write((int) (signCount & 0xff));
        return out.toByteArray();
    }

    private static byte[] buildAttestationObject(byte[] authData) {
        ByteArrayOutputStream out = new ByteArrayOutputStream(authData.length + 32);
        out.write(0xa3); // Map(3)
        writeCborText(out, "fmt");
        writeCborText(out, "none");
        writeCborText(out, "attStmt");
        out.write(0xa0); // Empty map {}
        writeCborText(out, "authData");
        writeCborBytes(out, authData);
        return out.toByteArray();
    }

    private static void writeCborHeader(ByteArrayOutputStream out, int majorType, int length) {
        int base = majorType << 5;
        if (length < 24) {
            out.write(base | length);
        } else if (length < 256) {
            out.write(base | 24);
            out.write(length);
        } else {
            out.write(base | 25);
            out.write((length >> 8) & 0xff);
            out.write(length & 0xff);
        }
    }

    private static void writeCborBytes(ByteArrayOutputStream out, byte[] data) {
        writeCborHeader(out, 2, data.length);
        out.write(data, 0, data.length);
    }

    private static void writeCborText(ByteArrayOutputStream out, String text) {
        byte[] utf8 = text.getBytes(StandardCharsets.UTF_8);
        writeCborHeader(out, 3, utf8.length);
        out.write(utf8, 0, utf8.length);
    }

    private static String base64UrlEncode(byte[] bytes) {
        return Base64.encodeToString(bytes, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
    }

    private static String normalizeBase64Url(String input) {
        if (input == null || input.isEmpty()) return "";
        try {
            byte[] decoded = Base64.decode(input, Base64.URL_SAFE | Base64.NO_PADDING | Base64.NO_WRAP);
            return base64UrlEncode(decoded);
        } catch (IllegalArgumentException ignored) {
            return input.replace('+', '-').replace('/', '_').replaceAll("=+$", "");
        }
    }

    private void finishPasskeyRequest(CancellationSignal cancellation, JavaScriptReplyProxy reply,
                                      String id, @Nullable JSONObject assertion, @Nullable String error) {
        if (passkeyCancellation != cancellation) return;
        passkeyCancellation = null;
        passkeyRequestId = null;
        activeBiometricPrompt = null;
        if (isAttendanceOriginUrl(webView.getUrl())) {
            sendPasskeyReply(reply, id, assertion, error);
        }
    }

    private void cancelPasskeyRequest() {
        CancellationSignal cancellation = passkeyCancellation;
        BiometricPrompt prompt = activeBiometricPrompt;
        passkeyCancellation = null;
        passkeyRequestId = null;
        activeBiometricPrompt = null;
        if (prompt != null) {
            try {
                prompt.cancelAuthentication();
            } catch (Exception ignored) {
            }
        }
        if (cancellation != null) cancellation.cancel();
    }

    private static void sendPasskeyReply(JavaScriptReplyProxy reply, String id,
                                         @Nullable JSONObject assertion, @Nullable String error) {
        try {
            JSONObject response = new JSONObject().put("id", id);
            if (assertion != null) {
                response.put("assertion", assertion);
                response.put("credential", assertion);
            } else {
                response.put("error", error);
            }
            reply.postMessage(response.toString());
        } catch (JSONException ignored) {
            // Reply values are strings and the JSON returned by Credential Manager.
        }
    }

    @Override
    protected void onSaveInstanceState(@NonNull Bundle outState) {
        super.onSaveInstanceState(outState);
        String url = webView.getUrl();
        if (launchError.getVisibility() != View.VISIBLE && url != null && isAttendanceUrl(Uri.parse(url))) {
            outState.putString(WEB_VIEW_ORIGIN_STATE, APP_URL);
            webView.saveState(outState);
        }
    }

    /**
     * JavaScript Interface that exposes hardware/installation stable device binding metadata.
     */
    public class AndroidDeviceBridge {
        private final android.content.Context mContext;

        public AndroidDeviceBridge(android.content.Context context) {
            this.mContext = context;
        }

        @android.webkit.JavascriptInterface
        public String getNativeDeviceId() {
            android.content.SharedPreferences prefs = mContext.getSharedPreferences("app_device_binding", android.content.Context.MODE_PRIVATE);
            String id = prefs.getString("native_device_id", null);
            if (id == null || id.trim().isEmpty()) {
                id = "and_" + java.util.UUID.randomUUID().toString().replace("-", "");
                prefs.edit().putString("native_device_id", id).apply();
            }
            return id;
        }

        @android.webkit.JavascriptInterface
        public String getNativeDeviceModel() {
            String manufacturer = Build.MANUFACTURER != null ? Build.MANUFACTURER : "";
            String model = Build.MODEL != null ? Build.MODEL : "Android Device";
            if (model.toLowerCase().startsWith(manufacturer.toLowerCase())) {
                return model;
            }
            return (manufacturer + " " + model).trim();
        }

        @android.webkit.JavascriptInterface
        public boolean isNativeApp() {
            return true;
        }

        @android.webkit.JavascriptInterface
        public String getLastIdentifier() {
            try {
                android.content.SharedPreferences prefs = mContext.getSharedPreferences("app_device_binding", android.content.Context.MODE_PRIVATE);
                return prefs.getString("last_user_identifier", "");
            } catch (Exception e) {
                return "";
            }
        }

        @android.webkit.JavascriptInterface
        public void setLastIdentifier(String identifier) {
            try {
                if (identifier != null && !identifier.trim().isEmpty()) {
                    android.content.SharedPreferences prefs = mContext.getSharedPreferences("app_device_binding", android.content.Context.MODE_PRIVATE);
                    prefs.edit().putString("last_user_identifier", identifier.trim()).apply();
                }
            } catch (Exception e) {}
        }

        @android.webkit.JavascriptInterface
        public boolean isBiometricAvailable() {
            try {
                BiometricManager bm = BiometricManager.from(MainActivity.this);
                return canUseBiometricPrompt();
            } catch (Exception e) {
                return false;
            }
        }

        @android.webkit.JavascriptInterface
        public boolean isStrongBiometricAvailable() {
            return canUseStrongBiometricPrompt();
        }

        @android.webkit.JavascriptInterface
        public boolean hasLocalPasskey() {
            String host = APP_ORIGIN.getHost();
            return host != null && findLatestKeystoreEntry(host) != null;
        }

        @android.webkit.JavascriptInterface
        public int getAppVersionCode() {
            return 20925;
        }

        @android.webkit.JavascriptInterface
        public void authenticateBiometric(final String title, final String subtitle, final String successJsCallback, final String errorJsCallback) {
            runOnUiThread(() -> {
                try {
                    Executor executor = ContextCompat.getMainExecutor(MainActivity.this);
                    BiometricPrompt bp = new BiometricPrompt(MainActivity.this, executor, new BiometricPrompt.AuthenticationCallback() {
                        @Override
                        public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                            super.onAuthenticationSucceeded(result);
                            if (webView != null && successJsCallback != null && !successJsCallback.trim().isEmpty()) {
                                webView.evaluateJavascript("if (typeof " + successJsCallback + " === 'function') { " + successJsCallback + "(); }", null);
                            }
                        }

                        @Override
                        public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                            super.onAuthenticationError(errorCode, errString);
                            boolean isUserCancelled = (errorCode == BiometricPrompt.ERROR_USER_CANCELED || errorCode == BiometricPrompt.ERROR_NEGATIVE_BUTTON);
                            if (webView != null && errorJsCallback != null && !errorJsCallback.trim().isEmpty()) {
                                String safeErr = errString.toString().replace("'", "\\'");
                                webView.evaluateJavascript("if (typeof " + errorJsCallback + " === 'function') { " + errorJsCallback + "('" + safeErr + "', " + errorCode + ", " + isUserCancelled + "); }", null);
                            }
                        }

                        @Override
                        public void onAuthenticationFailed() {
                            super.onAuthenticationFailed();
                            // Transient recognition failure: BiometricPrompt keeps listening on sensor
                        }
                    });

                    String promptTitle = (title != null && !title.trim().isEmpty()) ? title : "Sign In with Fingerprint";
                    String promptSub = (subtitle != null && !subtitle.trim().isEmpty()) ? subtitle : "Touch fingerprint sensor or verify identity to continue";

                    BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                            .setTitle(promptTitle)
                            .setSubtitle(promptSub)
                            .setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG | BiometricManager.Authenticators.BIOMETRIC_WEAK)
                            .setNegativeButtonText("Cancel")
                            .build();

                    bp.authenticate(promptInfo);
                } catch (Exception e) {
                    if (webView != null && errorJsCallback != null && !errorJsCallback.trim().isEmpty()) {
                        String safeErr = (e.getMessage() != null ? e.getMessage() : "Biometric error").replace("'", "\\'");
                        webView.evaluateJavascript("if (typeof " + errorJsCallback + " === 'function') { " + errorJsCallback + "('" + safeErr + "', -1, false); }", null);
                    }
                }
            });
        }
    }
}
