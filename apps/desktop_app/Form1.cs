using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Threading.Tasks;
using System.Windows.Forms;
using Microsoft.Web.WebView2.Core;
using Microsoft.Web.WebView2.WinForms;

namespace SmartAttendance;

public partial class Form1 : Form
{
    private const string AppUrl = "https://school-attendance-o0pm.onrender.com";
    private WebView2? webView;
    private Panel? splashPanel;
    private Label? statusLabel;
    private ProgressBar? splashProgress;
    private bool isFullscreen = false;
    private FormWindowState previousWindowState = FormWindowState.Normal;
    private FormBorderStyle previousBorderStyle = FormBorderStyle.Sizable;

    public Form1()
    {
        InitializeComponent();
        ConfigureFormAppearance();
        BuildSplashUI();
        this.Shown += async (s, e) => await InitializeWebViewAsync();
    }

    private void ConfigureFormAppearance()
    {
        this.Text = "Smart Attendance";
        this.BackColor = Color.FromArgb(17, 10, 10);
        this.ForeColor = Color.FromArgb(243, 231, 205);
        this.KeyPreview = true;
        this.KeyDown += Form1_KeyDown;

        string iconPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app.ico");
        if (File.Exists(iconPath))
        {
            try
            {
                this.Icon = new Icon(iconPath);
            }
            catch { }
        }
    }

    private void BuildSplashUI()
    {
        splashPanel = new Panel
        {
            Dock = DockStyle.Fill,
            BackColor = Color.FromArgb(17, 10, 10)
        };

        var container = new Panel
        {
            Width = 360,
            Height = 220,
            BackColor = Color.Transparent
        };
        container.Location = new Point(
            (this.ClientSize.Width - container.Width) / 2,
            (this.ClientSize.Height - container.Height) / 2
        );
        container.Anchor = AnchorStyles.None;

        var titleLabel = new Label
        {
            Text = "Smart Classroom Attendance",
            ForeColor = Color.FromArgb(243, 231, 205),
            Font = new Font("Segoe UI", 16F, FontStyle.Bold),
            TextAlign = ContentAlignment.MiddleCenter,
            Dock = DockStyle.Top,
            Height = 40
        };

        var subtitleLabel = new Label
        {
            Text = "Real-time School Attendance, QR & Biometrics",
            ForeColor = Color.FromArgb(179, 155, 130),
            Font = new Font("Segoe UI", 9.5F, FontStyle.Regular),
            TextAlign = ContentAlignment.MiddleCenter,
            Dock = DockStyle.Top,
            Height = 30
        };

        splashProgress = new ProgressBar
        {
            Style = ProgressBarStyle.Marquee,
            MarqueeAnimationSpeed = 30,
            Dock = DockStyle.Top,
            Height = 4
        };

        statusLabel = new Label
        {
            Text = "Launching application...",
            ForeColor = Color.FromArgb(207, 164, 111),
            Font = new Font("Segoe UI", 9F, FontStyle.Regular),
            TextAlign = ContentAlignment.MiddleCenter,
            Dock = DockStyle.Top,
            Height = 35
        };

        container.Controls.Add(statusLabel);
        container.Controls.Add(splashProgress);
        container.Controls.Add(subtitleLabel);
        container.Controls.Add(titleLabel);

        splashPanel.Controls.Add(container);
        this.Controls.Add(splashPanel);

        this.Resize += (s, e) =>
        {
            if (splashPanel != null && container != null)
            {
                container.Location = new Point(
                    Math.Max(0, (splashPanel.ClientSize.Width - container.Width) / 2),
                    Math.Max(0, (splashPanel.ClientSize.Height - container.Height) / 2)
                );
            }
        };
    }

    private async Task InitializeWebViewAsync()
    {
        try
        {
            if (statusLabel != null) statusLabel.Text = "Initializing application engine...";

            string userDataFolder = Path.Combine(
                Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
                "SmartAttendance",
                "DesktopCache"
            );
            Directory.CreateDirectory(userDataFolder);

            var env = await CoreWebView2Environment.CreateAsync(null, userDataFolder);

            webView = new WebView2
            {
                Dock = DockStyle.Fill,
                Visible = false
            };
            this.Controls.Add(webView);
            this.Controls.SetChildIndex(webView, 0);

            await webView.EnsureCoreWebView2Async(env);

            ConfigureCoreWebView2();

            if (statusLabel != null) statusLabel.Text = "Connecting to Smart Attendance...";
            webView.Source = new Uri(AppUrl);
        }
        catch (Exception ex)
        {
            if (statusLabel != null)
            {
                statusLabel.Text = "Initialization error: " + ex.Message;
                statusLabel.ForeColor = Color.Red;
            }
        }
    }

    private void ConfigureCoreWebView2()
    {
        if (webView == null || webView.CoreWebView2 == null) return;

        var settings = webView.CoreWebView2.Settings;
        settings.IsScriptEnabled = true;
        settings.AreDefaultScriptDialogsEnabled = true;
        settings.IsWebMessageEnabled = true;
        settings.AreDevToolsEnabled = false;
        settings.IsStatusBarEnabled = false;
        settings.AreDefaultContextMenusEnabled = true;
        settings.IsZoomControlEnabled = false;

        // Customize User Agent to denote official Windows native desktop application
        string defaultUa = settings.UserAgent;
        settings.UserAgent = defaultUa + " SmartAttendanceApp/1.0 (Windows Native Desktop)";

        // Handle permissions for camera (QR scanning), geolocation (GPS clock-in), and notifications
        webView.CoreWebView2.PermissionRequested += CoreWebView2_PermissionRequested;

        // Keep all links within the application window
        webView.CoreWebView2.NewWindowRequested += CoreWebView2_NewWindowRequested;

        // Update application window title with page title
        webView.CoreWebView2.DocumentTitleChanged += (s, e) =>
        {
            string title = webView.CoreWebView2.DocumentTitle;
            if (string.IsNullOrWhiteSpace(title) || title.Contains("http", StringComparison.OrdinalIgnoreCase))
            {
                this.Text = "Smart Attendance";
            }
            else
            {
                this.Text = title.Contains("Smart Attendance", StringComparison.OrdinalIgnoreCase)
                    ? title
                    : $"{title} - Smart Attendance";
            }
        };

        // Navigation handlers
        webView.NavigationStarting += (s, e) =>
        {
            string uri = e.Uri;
            if (uri.StartsWith("tel:") || uri.StartsWith("mailto:") || uri.StartsWith("sms:"))
            {
                e.Cancel = true;
                try
                {
                    Process.Start(new ProcessStartInfo(uri) { UseShellExecute = true });
                }
                catch { }
            }
        };

        webView.NavigationCompleted += (s, e) =>
        {
            if (e.IsSuccess)
            {
                // Auto-detect Render free tier cold-start screen and refresh automatically
                string pageTitle = webView.CoreWebView2.DocumentTitle ?? "";
                if (pageTitle.Contains("Application Loading", StringComparison.OrdinalIgnoreCase) ||
                    pageTitle.Contains("waking up", StringComparison.OrdinalIgnoreCase))
                {
                    if (statusLabel != null) statusLabel.Text = "Server waking up, retrying shortly...";
                    Task.Delay(4000).ContinueWith(_ =>
                    {
                        this.Invoke(new Action(() =>
                        {
                            webView?.Reload();
                        }));
                    });
                    return;
                }

                // Show WebView and hide splash once loaded
                if (splashPanel != null)
                {
                    splashPanel.Visible = false;
                }
                webView.Visible = true;
            }
            else
            {
                if (statusLabel != null)
                {
                    statusLabel.Text = "Loading failed: " + e.WebErrorStatus.ToString() + ". Retrying...";
                }
                Task.Delay(3000).ContinueWith(_ =>
                {
                    this.Invoke(new Action(() =>
                    {
                        webView?.Reload();
                    }));
                });
            }
        };
    }

    private void CoreWebView2_PermissionRequested(object? sender, CoreWebView2PermissionRequestedEventArgs e)
    {
        switch (e.PermissionKind)
        {
            case CoreWebView2PermissionKind.Camera:
            case CoreWebView2PermissionKind.Microphone:
            case CoreWebView2PermissionKind.Geolocation:
            case CoreWebView2PermissionKind.Notifications:
                e.State = CoreWebView2PermissionState.Allow;
                e.Handled = true;
                break;
            default:
                break;
        }
    }

    private void CoreWebView2_NewWindowRequested(object? sender, CoreWebView2NewWindowRequestedEventArgs e)
    {
        e.Handled = true;
        string targetUri = e.Uri;

        if (targetUri.StartsWith("http://", StringComparison.OrdinalIgnoreCase) ||
            targetUri.StartsWith("https://", StringComparison.OrdinalIgnoreCase))
        {
            // Navigate inside the current standalone app window instead of opening a browser!
            webView?.CoreWebView2.Navigate(targetUri);
        }
        else
        {
            try
            {
                Process.Start(new ProcessStartInfo(targetUri) { UseShellExecute = true });
            }
            catch { }
        }
    }

    private void Form1_KeyDown(object? sender, KeyEventArgs e)
    {
        if (e.KeyCode == Keys.F11)
        {
            ToggleFullscreen();
            e.Handled = true;
        }
        else if (e.KeyCode == Keys.F5 || (e.Control && e.KeyCode == Keys.R))
        {
            webView?.Reload();
            e.Handled = true;
        }
        else if (e.Alt && e.KeyCode == Keys.Left)
        {
            if (webView != null && webView.CanGoBack)
            {
                webView.GoBack();
                e.Handled = true;
            }
        }
    }

    private void ToggleFullscreen()
    {
        if (!isFullscreen)
        {
            previousWindowState = this.WindowState;
            previousBorderStyle = this.FormBorderStyle;

            this.FormBorderStyle = FormBorderStyle.None;
            this.WindowState = FormWindowState.Normal;
            this.WindowState = FormWindowState.Maximized;
            isFullscreen = true;
        }
        else
        {
            this.FormBorderStyle = previousBorderStyle;
            this.WindowState = previousWindowState;
            isFullscreen = false;
        }
    }
}
