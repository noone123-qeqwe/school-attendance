# Smart Attendance project skills

Open `school_attendance` as the Antigravity workspace. The project discovery manifest registers `.agent/skills/`. The existing UI/UX Pro Max skill lives in `.agents/skills/`. Antigravity indexes skill descriptions and loads the full instructions when selected. [Antigravity skill discovery](https://antigravity.google/docs/customization-discovery/)

| Task | Start with | What to check in this project |
| --- | --- | --- |
| Laravel backend architecture, API implementation or a localized bug | `laravel-development-workflow` | Trace the route, request, policy, service and persistence boundary. Verify the change with the configured PHPUnit suite. |
| Security auditing, authentication, authorization or API security | `laravel-security-audit` | Check the WebAuthn challenge and device binding, role and ownership checks, response fields and throttling. |
| Personal data, biometrics or location privacy | `privacy-by-design` | Trace consent, collection, retention, storage and logging. Derive retention decisions from school policy. |
| Slow database queries or reports | `sql-optimization-patterns` | Compare results and query plans on synthetic data. Use the deployment engine for timings. PHPUnit uses SQLite and the Render manifests specify PostgreSQL. |
| Browser and end-to-end tests | `e2e-testing-patterns` | Exercise enrollment, sign-in, QR, denied location and retry paths with test accounts. Verify physical biometric prompts separately on a device. |
| Accessibility | `accessibility-compliance-accessibility-audit` | Check keyboard navigation, focus recovery, screen-reader announcements, contrast and reflow in the affected flow. |
| Systematic debugging across layers | `debugging-strategies` | Identify where a synthetic request first diverges across the browser, Android WebView, Python face matcher or Laravel service. |
| Application performance | `performance-optimizer` | Compare the same local workload before and after. Keep cache entries scoped to the authorized user or class. |
| Code review or refactoring review | `code-review-checklist` | Review the actual diff and its callers. Check ownership, duplicate attendance on retry and regression coverage. |
| Frontend design, UI/UX or responsive design | `ui-ux-pro-max` (existing) | Use the attendance design system and test the affected screen at phone, tablet and desktop widths. |
| Explicit request for Superpowers or help choosing a workflow | `using-superpowers` | Select the first relevant skill. The project’s on-demand rule takes precedence over the upstream suggestion to load a skill at 1% relevance. |

## Installation and maintenance

The ten imported skill directories are unmodified copies from release `v21.3.0`, commit `85d3a54b73ebf23683955bda67bf906aca527e57`. They contain 16 Markdown files. Installation used the upstream release-source copy method and verified the files against their release Git blobs and reviewed SHA-256 hashes. [Pinned source](https://github.com/sickn33/agentic-awesome-skills/tree/85d3a54b73ebf23683955bda67bf906aca527e57/skills) · [Installation guide](https://github.com/sickn33/agentic-awesome-skills/blob/main/docs/users/getting-started.md#install-and-update-without-npm) · [Third-party notices](THIRD-PARTY-NOTICES.md)

Docker and Render exclude both agent directories. Tailwind also excludes them from its source scan, so examples in the playbooks cannot generate application CSS during local builds.

For an update, review the complete selected directories at the new revision, including any added scripts or dependencies, and verify their contents before replacing them. To inspect the install locally, run `gh skill list --dir .agent/skills` from the repository root.
