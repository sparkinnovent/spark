# SUS Usability Study — GDIID 2025

A ready-to-use System Usability Scale (SUS) study for the Spark / GDIID conference
system, with a Google Sheets backend that works entirely on Apps Script (no
server, no database, no cost).

| File | Purpose |
|---|---|
| `../sus-study.html` | The questionnaire participants fill in. |
| `Code.gs` | Apps Script web app that validates, scores and stores responses. |
| `appsscript.json` | Manifest (timezone, anonymous web app access). |

---

## 1. Create the Google Sheet and script

1. Go to <https://sheets.google.com> and create a blank spreadsheet named
   **GDIID SUS Study 2025**.
2. In that spreadsheet choose **Extensions → Apps Script**.
3. Delete the placeholder `myFunction` and paste the whole of `Code.gs`.
4. Open **Project Settings** (gear icon) and tick *Show "appsscript.json" manifest file*.
5. Replace the manifest contents with `appsscript.json`, then save (Ctrl+S).
6. Rename the project to **GDIID SUS Endpoint**.

> Editing the manifest through the UI is optional — the only fields that matter
> are `timeZone` and `webapp.access`. You can also set those when deploying.

## 2. Prepare the sheets

In the Apps Script editor, pick the function **setup** and press **Run**.
Approve the permission prompt (the script needs access to the sheet it lives in).

This creates three tabs:

- **SUS Responses** — one row per participant, with raw ratings, per-item points,
  score, grade, adjective rating, comments and quality flags.
- **SUS Summary** — live statistics: n, mean, median, SD, min/max, benchmark
  comparison and acceptability bands.
- **SUS Log** — errors and mail failures (created only when needed).

Run **insertSampleResponse** once if you want to see a populated row and a
non-empty summary, then delete that row before the real study starts.

## 3. Deploy the web app

1. **Deploy → New deployment**.
2. Select type **Web app**.
3. Configure:
   - **Description**: `SUS endpoint v1`
   - **Execute as**: `Me`
   - **Who has access**: `Anyone`
4. **Deploy**, then copy the **Web app URL**. It looks like:

   ```
   https://script.google.com/macros/s/AKfycb.../exec
   ```

   The URL **must** end in `/exec` (not `/dev`).

5. Paste the URL into `CONFIG.endpoint` inside `sus-study.html`:

   ```js
   const CONFIG = {
     endpoint: "https://script.google.com/macros/s/AKfycb.../exec",
     formVersion: "1.0",
     storagePrefix: "gdiid_sus_"
   };
   ```

6. Open the deployment URL in a browser. You should see JSON such as
   `{"ok":true,"service":"GDIID SUS study","responses":0,...}`. That confirms
   the endpoint is live.

> **After editing `Code.gs`** you must create a *new deployment* (or use
> **Deploy → Manage deployments → Edit → Version: New version**) — otherwise the
> old code keeps serving.

## 4. Test before the study

1. Open `sus-study.html` in a browser (double-clicking the file is fine).
2. Answer all 10 statements, submit, and confirm the result panel appears.
3. Refresh the Google Sheet — a new row should be present in **SUS Responses**
   and **SUS Summary** should update.
4. Submit the *same* payload twice (or refresh and resend) — the script detects
   duplicate `submissionId` values, so no double rows are created.
5. Check the browser console for errors; there should be none.

Score cross-check — the SUS formula is `sum(odd: rating-1, even: 5-rating) × 2.5`, so:

| Answers (1…10) | Expected score |
|---|---|
| `5,1,5,1,5,1,5,1,5,1` | **100** |
| `1,5,1,5,1,5,1,5,1,5` | **0** |
| all `3` | **50** |
| all `5` | **50** (agreeing with every statement is contradictory) |

The script recomputes the score server-side from the raw ratings, so these hold even if the page is tampered with.

## 5. Running the session

- Host `sus-study.html` anywhere: the same site as the app
  (e.g. `https://yourdomain.com/sus-study.html`), Netlify, GitHub Pages, or a
  shared Google Drive link.
- Give each participant the link **after** they finish the tasks.
- The Participant ID (`GDIID-SUS-YYMMDD-XXXX`) is generated per browser, so a
  shared lab machine needs **Clear form → Start a new response**, or the
  facilitator can use private windows.
- Answers autosave to `localStorage`, so an accidental refresh does not lose
  progress. A submit that fails (weak Wi-Fi) is queued on the device and can be
  retried from the result panel.
- If `endpoint` is left empty the form runs in **preview mode**: it scores and
  displays the result but sends nothing. Useful for rehearsals.

### Privacy

- Name and email are optional and clearly labelled.
- The form asks for consent before submission.
- `userAgent`, `pageUrl` and timezone are stored for triage. Delete those columns
  before sharing the sheet publicly, or remove them from `COLUMNS` and
  `buildPayload()` if your ethics approval requires it.
- Keep the spreadsheet private. Do not publish it.

## 6. Analysing results

The **SUS Summary** tab already reports what a write-up needs:

| Statistic | Where |
|---|---|
| n, mean, median, SD, min, max | SUS Summary rows 4–9 |
| Acceptability bands | SUS Summary rows 14–16 |
| Comparison with the 68 benchmark | SUS Summary rows 11–13 |

Interpretation thresholds (Bangor, Kortum & Miller, 2008; Sauro & Lewis, 2016):

| Range | Adjective | Grade | Acceptability |
|---|---|---|---|
| 85.1 – 100 | Excellent | A+ / A | Acceptable |
| 71.1 – 85.0 | Good | B+ – C+ | Acceptable |
| 51.7 – 71.0 | Fair | C – D | Marginal |
| 0 – 51.6 | Poor | F | Not acceptable |

Report like this:

> Participants (n = 24) rated GDIID at M = 76.3 (SD = 11.8, Mdn = 78.9),
> above the 68-point industry benchmark and in the "Good" / grade B+ band,
> indicating an acceptable level of usability.

### Per-item diagnosis

Low scores on individual items point at specific fixes:

| Item | If it scores low, the issue is… |
|---|---|
| 1, 3, 7, 9 | Perceived ease of use, learnability, confidence (positive items) |
| 2, 6 | Inconsistency and unnecessary complexity |
| 4 | Too much dependence on technical support / unclear self-service |
| 5 | Poor integration between modules (payment, abstract, LOA, certificate) |
| 8 | Cumbersome workflows — usually long forms or too many steps |
| 10 | Onboarding and instruction quality |

Because odd/even items are scored in opposite directions, check the **Points**
columns rather than the raw ratings.

### Exporting

`File → Download → Microsoft Excel` in Sheets, or copy the **SUS Responses** tab
into SPSS / JASP / R. The `score` column is numeric and ready for `mean()`,
`sd()` and a one-sample t-test against 68:

```
t.test(responses$score, mu = 68)
```

## 7. Optional hardening

| Setting in `Code.gs` | Effect |
|---|---|
| `SHARED_SECRET` | Requires a `token` field. Add `token: "…"` to `buildPayload()` in the HTML. Stops casual spam; it is not real security (the value is visible in the page source). |
| `NOTIFY_ON_SUBMIT` + `NOTIFY_EMAIL` | Emails you each submission. Noisy above ~20 participants; keep it off during the session. |
| `MIN_DURATION_SECONDS` | Flags suspiciously fast submissions in the `Quality Flag` column instead of rejecting them. |
| `SPREADSHEET_ID` | Use when the script is standalone (not bound to a sheet). |

Google Apps Script quotas (consumer accounts): ~20,000 URL fetches per day and
100 emails per day — far beyond what a usability study needs.

## 8. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Form says "Preview mode" | `CONFIG.endpoint` is empty. Paste the `/exec` URL. |
| `Failed to fetch` in the console | Wrong URL (usually `/dev`), the deployment is not "Anyone", or the script was edited without redeploying. |
| `ok:false, Invalid token` | `SHARED_SECRET` is set but the page sends no matching token. |
| Row appears but the score is blank | The `score` column was overwritten or the row was hand-edited; the script always writes it. |
| Two rows for one participant | Duplicate detection relies on `submissionId`; hand-created rows bypass it. Delete extras manually. |
| Summary tab shows `—` | No numeric scores yet. Submit a response or run **insertSampleResponse**. |
| Permission prompt mentions "unverified app" | Normal for personal Apps Script projects. Choose *Advanced → Go to project*. |

## 9. References

- Brooke, J. (1996). SUS: A "quick and dirty" usability scale. In *Usability
  Evaluation in Industry* (pp. 189–194). Taylor & Francis.
- Bangor, A., Kortum, P. T., & Miller, J. T. (2008). An empirical evaluation of
  the System Usability Scale. *International Journal of Human-Computer
  Interaction, 24*(6), 574–594.
- Sauro, J., & Lewis, J. R. (2016). *Quantifying the User Experience* (2nd ed.).
  Morgan Kaufmann.
- Lewis, J. R., & Sauro, J. (2018). Item benchmarks for the System Usability
  Scale. *Journal of Usability Studies, 13*(3), 158–167.
