/**
 * GDIID 2025 — SUS Usability Study backend
 * Google Apps Script Web App that receives submissions from sus-study.html
 * and stores them in Google Sheets.
 *
 * Setup: see README.md in this folder.
 */

// ---------------------------------------------------------------------------
// CONFIGURATION
// ---------------------------------------------------------------------------

/** Leave empty to use the spreadsheet this script is bound to. */
var SPREADSHEET_ID = '';

/** Tab that stores one row per response. Created if missing. */
var RESPONSES_SHEET = 'SUS Responses';

/** Tab that stores aggregate statistics. Created if missing. */
var SUMMARY_SHEET = 'SUS Summary';

/** Tab that stores deleted/duplicate attempts (audit trail). */
var LOG_SHEET = 'SUS Log';

/** Optional. If set, submissions must include a matching "token" field. */
var SHARED_SECRET = '';

/** Set true to email the facilitator on each submission. */
var NOTIFY_EMAIL = '';
var NOTIFY_ON_SUBMIT = false;

/** Minimum seconds between page load and submit; shorter is logged as suspicious. */
var MIN_DURATION_SECONDS = 5;

/** Benchmark constants (Bangor et al. 2008 / Sauro-Lewis curved grading). */
var BENCHMARK_AVERAGE = 68;

// ---------------------------------------------------------------------------
// Column definitions — order here defines the sheet order.
// ---------------------------------------------------------------------------
var COLUMNS = [
  { key: 'receivedAt',        label: 'Received At (server)' },
  { key: 'submittedAt',       label: 'Submitted At (client)' },
  { key: 'participantId',     label: 'Participant ID' },
  { key: 'submissionId',      label: 'Submission ID' },
  { key: 'name',              label: 'Name' },
  { key: 'email',             label: 'Email' },
  { key: 'role',              label: 'Role' },
  { key: 'discipline',        label: 'Field of Expertise' },
  { key: 'device',            label: 'Device' },
  { key: 'experience',        label: 'Experience' },
  { key: 'modules',           label: 'Modules Tested' },
  { key: 'sus1',              label: 'SUS 1' },
  { key: 'sus2',              label: 'SUS 2' },
  { key: 'sus3',              label: 'SUS 3' },
  { key: 'sus4',              label: 'SUS 4' },
  { key: 'sus5',              label: 'SUS 5' },
  { key: 'sus6',              label: 'SUS 6' },
  { key: 'sus7',              label: 'SUS 7' },
  { key: 'sus8',              label: 'SUS 8' },
  { key: 'sus9',              label: 'SUS 9' },
  { key: 'sus10',             label: 'SUS 10' },
  { key: 'points1',           label: 'Points 1' },
  { key: 'points2',           label: 'Points 2' },
  { key: 'points3',           label: 'Points 3' },
  { key: 'points4',           label: 'Points 4' },
  { key: 'points5',           label: 'Points 5' },
  { key: 'points6',           label: 'Points 6' },
  { key: 'points7',           label: 'Points 7' },
  { key: 'points8',           label: 'Points 8' },
  { key: 'points9',           label: 'Points 9' },
  { key: 'points10',          label: 'Points 10' },
  { key: 'score',             label: 'SUS Score' },
  { key: 'grade',             label: 'Grade' },
  { key: 'adjective',         label: 'Adjective' },
  { key: 'acceptability',     label: 'Acceptability' },
  { key: 'recommend',         label: 'Recommend (0-10)' },
  { key: 'liked',             label: 'What Worked Well' },
  { key: 'improve',           label: 'What To Improve' },
  { key: 'comments',          label: 'Other Comments' },
  { key: 'durationSeconds',   label: 'Duration (s)' },
  { key: 'qualityFlag',       label: 'Quality Flag' },
  { key: 'formVersion',       label: 'Form Version' },
  { key: 'pageUrl',           label: 'Page URL' },
  { key: 'userAgent',         label: 'User Agent' },
  { key: 'language',          label: 'Language' },
  { key: 'timezone',          label: 'Timezone' }
];

var SUS_STATEMENTS = [
  'I think that I would like to use this system frequently.',
  'I found the system unnecessarily complex.',
  'I thought the system was easy to use.',
  'I think that I would need the support of a technical person to be able to use this system.',
  'I found the various functions in this system were well integrated.',
  'I thought there was too much inconsistency in this system.',
  'I would imagine that most people would learn to use this system very quickly.',
  'I found the system very cumbersome to use.',
  'I felt very confident using the system.',
  'I needed to learn a lot of things before I could get going with this system.'
];

// ---------------------------------------------------------------------------
// HTTP entry points
// ---------------------------------------------------------------------------

/**
 * POST /exec — accepts a JSON body (sent as text/plain to avoid a CORS
 * preflight) and appends one row.
 */
function doPost(e) {
  try {
    var body = parseBody(e);
    if (SHARED_SECRET && String(body.token || '') !== String(SHARED_SECRET)) {
      return jsonOut({ ok: false, error: 'Invalid token.' });
    }

    var clean = validate(body);
    var ss = getSpreadsheet();
    var sheet = getOrCreateSheet(ss, RESPONSES_SHEET, COLUMNS.map(function (c) { return c.label; }));

    // Idempotency: a retried submission must not create a second row.
    if (findRowBySubmissionId(sheet, clean.submissionId) > 0) {
      return jsonOut({ ok: true, duplicate: true, message: 'Already recorded.' });
    }

    var row = COLUMNS.map(function (col) {
      if (col.key === 'receivedAt') return new Date();
      return clean[col.key] !== undefined ? clean[col.key] : '';
    });

    sheet.appendRow(row);
    var lastRow = sheet.getLastRow();
    try {
      formatRow(sheet, lastRow);
    } catch (formatErr) {
      logEvent('Row formatting failed: ' + formatErr);
    }

    // Statistics are a convenience, never a reason to report failure to a participant.
    try {
      refreshSummary(ss);
    } catch (summaryErr) {
      logEvent('Summary refresh failed: ' + summaryErr);
    }

    if (NOTIFY_ON_SUBMIT && NOTIFY_EMAIL) {
      try {
        MailApp.sendEmail({
          to: NOTIFY_EMAIL,
          subject: 'SUS response — ' + clean.participantId + ' (' + clean.score + ')',
          body: 'Score: ' + clean.score + ' (' + clean.grade + ', ' + clean.adjective + ')\n' +
                'Role: ' + clean.role + '\n' +
                'Participant: ' + clean.participantId + '\n\n' +
                'Worked well:\n' + clean.liked + '\n\n' +
                'To improve:\n' + clean.improve
        });
      } catch (mailErr) {
        logEvent('Mail failed: ' + mailErr);
      }
    }

    return jsonOut({
      ok: true,
      score: clean.score,
      grade: clean.grade,
      adjective: clean.adjective,
      acceptability: clean.acceptability,
      participantId: clean.participantId,
      row: lastRow
    });
  } catch (err) {
    logEvent('ERROR: ' + (err && err.stack ? err.stack : err));
    return jsonOut({ ok: false, error: String(err && err.message ? err.message : err) });
  }
}

/**
 * GET /exec — health check + live summary.
 * Opening the deployment URL in a browser should return ok:true.
 */
function doGet(e) {
  try {
    var ss = getSpreadsheet();
    var stats = computeStats(ss);
    return jsonOut({
      ok: true,
      service: 'GDIID SUS study',
      responses: stats.count,
      average: stats.average,
      median: stats.median,
      sd: stats.sd,
      message: stats.count
        ? 'Collecting responses.'
        : 'Endpoint is live. No responses yet.'
    });
  } catch (err) {
    return jsonOut({ ok: false, error: String(err && err.message ? err.message : err) });
  }
}

// ---------------------------------------------------------------------------
// Validation and scoring
// ---------------------------------------------------------------------------

function parseBody(e) {
  if (!e || !e.postData) throw new Error('Empty request body.');
  var raw = e.postData.contents || '';
  if (!raw) throw new Error('Empty request body.');
  var data;
  try {
    data = JSON.parse(raw);
  } catch (err) {
    // Fallback for a normal HTML form post.
    data = e.parameter || {};
  }
  if (!data || typeof data !== 'object') throw new Error('Body must be a JSON object.');
  return data;
}

function validate(body) {
  var sus = body.sus;
  if (!sus) throw new Error('Missing "sus" answers.');
  if (typeof sus === 'string') {
    try { sus = JSON.parse(sus); } catch (err) { sus = sus.split(','); }
  }
  if (!Array.isArray(sus) || sus.length !== 10) {
    throw new Error('Expected exactly 10 SUS answers.');
  }

  var numbers = sus.map(function (v) { return Number(v); });
  numbers.forEach(function (v, i) {
    if (!isFinite(v) || v < 1 || v > 5 || Math.floor(v) !== v) {
      throw new Error('Answer ' + (i + 1) + ' must be a whole number from 1 to 5.');
    }
  });

  // Recompute server-side so a tampered score cannot enter the dataset.
  var points = numbers.map(function (v, i) { return (i % 2 === 0) ? (v - 1) : (5 - v); });
  var score = computeSus(numbers);

  var duration = Math.max(0, Number(body.durationSeconds) || 0);
  var flags = [];
  if (duration > 0 && duration < MIN_DURATION_SECONDS) flags.push('very fast');
  if (!body.participantId) flags.push('no participant id');
  if (body.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(String(body.email))) flags.push('bad email');

  var record = {
    submittedAt: body.submittedAt ? parseDate(body.submittedAt) : new Date(),
    participantId: str(body.participantId, 60),
    submissionId: str(body.submissionId, 80) || 'NOKEY-' + new Date().getTime(),
    name: str(body.name, 120),
    email: str(body.email, 160),
    role: str(body.role, 80),
    discipline: str(body.discipline, 80),
    device: str(body.device, 60),
    experience: str(body.experience, 80),
    modules: Array.isArray(body.modules) ? body.modules.join('; ') : str(body.modules, 400),
    score: score,
    grade: gradeFor(score),
    adjective: adjectiveFor(score),
    acceptability: acceptabilityFor(score),
    recommend: clampInt(body.recommend, 0, 10),
    liked: str(body.liked, 1200),
    improve: str(body.improve, 1200),
    comments: str(body.comments, 800),
    durationSeconds: duration,
    qualityFlag: flags.join('; '),
    formVersion: str(body.formVersion, 20),
    pageUrl: str(body.pageUrl, 400),
    userAgent: str(body.userAgent, 400),
    language: str(body.language, 30),
    timezone: str(body.timezone, 60),
    token: body.token
  };

  numbers.forEach(function (v, i) { record['sus' + (i + 1)] = v; });
  points.forEach(function (v, i) { record['points' + (i + 1)] = v; });

  return record;
}

/**
 * Standard SUS scoring: odd statements contribute (rating - 1),
 * even statements contribute (5 - rating); the total is multiplied by 2.5.
 */
function computeSus(raw) {
  var total = 0;
  for (var i = 0; i < 10; i++) {
    var v = Number(raw[i]);
    total += (i % 2 === 0) ? (v - 1) : (5 - v);
  }
  return Math.round(total * 2.5 * 10) / 10;
}

function gradeFor(score) {
  if (score >= 84.1) return 'A+';
  if (score >= 80.8) return 'A';
  if (score >= 78.9) return 'A-';
  if (score >= 77.2) return 'B+';
  if (score >= 74.1) return 'B';
  if (score >= 72.6) return 'B-';
  if (score >= 71.1) return 'C+';
  if (score >= 65.0) return 'C';
  if (score >= 62.7) return 'C-';
  if (score >= 51.7) return 'D';
  return 'F';
}

function adjectiveFor(score) {
  if (score >= 85.1) return 'Excellent';
  if (score >= 71.1) return 'Good';
  if (score >= 51.7) return 'Fair';
  return 'Poor';
}

function acceptabilityFor(score) {
  if (score >= 71.1) return 'Acceptable';
  if (score >= 51.7) return 'Marginal';
  return 'Not acceptable';
}

// ---------------------------------------------------------------------------
// Sheet helpers
// ---------------------------------------------------------------------------

function getSpreadsheet() {
  if (SPREADSHEET_ID) return SpreadsheetApp.openById(SPREADSHEET_ID);
  var active = SpreadsheetApp.getActiveSpreadsheet();
  if (active) return active;
  throw new Error('No spreadsheet. Set SPREADSHEET_ID in Code.gs.');
}

function getOrCreateSheet(ss, name, header) {
  var sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
  }
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, header.length).setValues([header]);
    sheet.getRange(1, 1, 1, header.length)
      .setFontWeight('bold')
      .setBackground('#3b0764')
      .setFontColor('#ffffff')
      .setVerticalAlignment('middle');
    sheet.setFrozenRows(1);
    sheet.setRowHeight(1, 34);
  }
  return sheet;
}

function findRowBySubmissionId(sheet, submissionId) {
  if (!submissionId || sheet.getLastRow() < 2) return 0;
  var col = COLUMNS.map(function (c) { return c.key; }).indexOf('submissionId') + 1;
  var values = sheet.getRange(2, col, sheet.getLastRow() - 1, 1).getValues();
  for (var i = 0; i < values.length; i++) {
    if (String(values[i][0]) === String(submissionId)) return i + 2;
  }
  return 0;
}

function formatRow(sheet, row) {
  var width = COLUMNS.length;
  sheet.getRange(row, 1, 1, width).setVerticalAlignment('top');
  var scoreCol = COLUMNS.map(function (c) { return c.key; }).indexOf('score') + 1;
  sheet.getRange(row, scoreCol).setNumberFormat('0.0');
  sheet.getRange(row, 1).setNumberFormat('yyyy-mm-dd hh:mm:ss');
  sheet.getRange(row, 2).setNumberFormat('yyyy-mm-dd hh:mm:ss');
  // Wrap only the long free-text columns.
  ['liked', 'improve', 'comments', 'userAgent'].forEach(function (key) {
    var idx = COLUMNS.map(function (c) { return c.key; }).indexOf(key) + 1;
    sheet.getRange(row, idx).setWrap(true);
  });
}

// ---------------------------------------------------------------------------
// Statistics
// ---------------------------------------------------------------------------

function computeStats(ss) {
  var sheet = ss.getSheetByName(RESPONSES_SHEET);
  if (!sheet || sheet.getLastRow() < 2) {
    return { count: 0, average: null, median: null, sd: null, min: null, max: null, scores: [] };
  }
  var scoreCol = COLUMNS.map(function (c) { return c.key; }).indexOf('score') + 1;
  var values = sheet.getRange(2, scoreCol, sheet.getLastRow() - 1, 1).getValues();
  // Keep a genuine score of 0; only skip empty or unreadable cells.
  var scores = values.map(function (r) { return r[0]; })
    .filter(function (cell) {
      return cell !== '' && cell !== null && cell !== undefined && isFinite(Number(cell));
    })
    .map(function (cell) { return Number(cell); });

  if (!scores.length) {
    return { count: 0, average: null, median: null, sd: null, min: null, max: null, scores: [] };
  }

  var n = scores.length;
  var sum = scores.reduce(function (a, b) { return a + b; }, 0);
  var mean = sum / n;
  var variance = scores.reduce(function (a, b) { return a + Math.pow(b - mean, 2); }, 0) / (n > 1 ? n - 1 : 1);
  var sorted = scores.slice().sort(function (a, b) { return a - b; });
  var mid = Math.floor(n / 2);
  var median = n % 2 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;

  return {
    count: n,
    average: Math.round(mean * 10) / 10,
    median: Math.round(median * 10) / 10,
    sd: Math.round(Math.sqrt(variance) * 10) / 10,
    min: Math.min.apply(null, scores),
    max: Math.max.apply(null, scores),
    scores: scores
  };
}

function refreshSummary(ss) {
  var stats = computeStats(ss);
  var sheet = ss.getSheetByName(SUMMARY_SHEET) || ss.insertSheet(SUMMARY_SHEET);
  var segments = computeSegments(ss);
  var rows = [
    ['GDIID 2025 — SUS Study Summary', ''],
    ['Updated', new Date()],
    ['', ''],
    ['Responses (n)', stats.count],
    ['Mean SUS score', stats.average === null ? '—' : stats.average],
    ['Median SUS score', stats.median === null ? '—' : stats.median],
    ['Standard deviation', stats.sd === null ? '—' : stats.sd],
    ['Lowest score', stats.min === null ? '—' : stats.min],
    ['Highest score', stats.max === null ? '—' : stats.max],
    ['', ''],
    ['Benchmark average', BENCHMARK_AVERAGE],
    ['Difference vs benchmark', stats.average === null ? '—' : Math.round((stats.average - BENCHMARK_AVERAGE) * 10) / 10],
    ['Above benchmark?', stats.average === null ? '—' : (stats.average >= BENCHMARK_AVERAGE ? 'Yes' : 'No')],
    ['Acceptable (>= 71.1)', stats.count ? countAtLeast(stats.scores, 71.1) + ' of ' + stats.count : '—'],
    ['Marginal (51.7 - 71.0)', stats.count ? countBetween(stats.scores, 51.7, 71.1) + ' of ' + stats.count : '—'],
    ['Not acceptable (< 51.7)', stats.count ? countBelow(stats.scores, 51.7) + ' of ' + stats.count : '—'],
    ['', ''],
    ['Interpretation', '68 = industry average. 71.1+ = acceptable. 80.8+ = grade A. Report the mean, median, SD and n together.'],
    ['', ''],
    ['BREAKDOWN BY FIELD', 'n'],
    [segLabel('Science & Technology'), segLine(segments.byDiscipline['Science & Technology'])],
    [segLabel('Social Science'), segLine(segments.byDiscipline['Social Science'])],
    [segLabel('Other / Interdisciplinary'), segLine(segments.byDiscipline['Other / Interdisciplinary'])],
    ['', ''],
    ['BREAKDOWN BY ROLE', 'n'],
    [segLabel('Participant / Presenter'), segLine(segments.byRole['Participant / Presenter'])],
    [segLabel('Conference administrator'), segLine(segments.byRole['Conference administrator'])],
    [segLabel('Secretariat committee'), segLine(segments.byRole['Secretariat committee'])],
    [segLabel('Reviewer / Evaluator'), segLine(segments.byRole['Reviewer / Evaluator'])],
    ['', ''],
    ['ROLE GROUP', 'n'],
    [segLabel('Participant-side'), segLine(segments.roleGroup.participant)],
    [segLabel('Administrator-side'), segLine(segments.roleGroup.admin)],
    ['', ''],
    ['CROSS-TAB: mean SUS (n)', 'Science & Technology'],
    ['Participant-side', crossCell(segments.cross.participantST)],
    ['Administrator-side', crossCell(segments.cross.adminST)],
    ['', 'Social Science'],
    ['Participant-side', crossCell(segments.cross.participantSS)],
    ['Administrator-side', crossCell(segments.cross.adminSS)],
    ['', 'Other / Interdisciplinary'],
    ['Participant-side', crossCell(segments.cross.participantOther)],
    ['Administrator-side', crossCell(segments.cross.adminOther)]
  ];

  sheet.clear();
  sheet.getRange(1, 1, rows.length, 2).setValues(rows);
  sheet.getRange(1, 1, 1, 2).setFontWeight('bold').setFontSize(13).setFontColor('#3b0764');
  sheet.getRange(2, 1, 1, 2).setFontColor('#666666');
  sheet.getRange(1, 1, rows.length, 2).setVerticalAlignment('middle');
  sheet.setColumnWidth(1, 250);
  sheet.setColumnWidth(2, 320);
  sheet.getRange(1, 1, 1, 2).setBackground('#f3e8ff');
  // Emphasise the breakdown headers.
  [20, 25, 32, 36, 41].forEach(function (r) {
    if (r <= rows.length) sheet.getRange(r, 1, 1, 2).setFontWeight('bold').setBackground('#faf4ff');
  });
}

function segLabel(name) {
  return name;
}

function segLine(seg) {
  if (!seg || !seg.count) return 'no responses';
  return 'n=' + seg.count + ' · M=' + seg.average + ' (SD ' + seg.sd + ')';
}

function crossCell(seg) {
  return segCount(seg);
}

function segCount(seg) {
  if (!seg || !seg.count) return 'n=0';
  return 'n=' + seg.count + ' · M=' + seg.average;
}

/**
 * Groups scores by discipline, by role, by participant/admin and by the
 * discipline x role cross-tab.
 */
function computeSegments(ss) {
  var empty = function () { return { count: 0, average: null, sd: null, scores: [] }; };
  var out = {
    byDiscipline: {
      'Science & Technology': empty(),
      'Social Science': empty(),
      'Other / Interdisciplinary': empty()
    },
    byRole: {
      'Participant / Presenter': empty(),
      'Conference administrator': empty(),
      'Secretariat committee': empty(),
      'Reviewer / Evaluator': empty()
    },
    roleGroup: { participant: empty(), admin: empty() },
    cross: {
      participantST: empty(), adminST: empty(),
      participantSS: empty(), adminSS: empty(),
      participantOther: empty(), adminOther: empty()
    }
  };

  var sheet = ss.getSheetByName(RESPONSES_SHEET);
  if (!sheet || sheet.getLastRow() < 2) return out;

  var keys = COLUMNS.map(function (c) { return c.key; });
  var lastCol = COLUMNS.length;
  var values = sheet.getRange(2, 1, sheet.getLastRow() - 1, lastCol).getValues();
  var scoreIdx = keys.indexOf('score');
  var discIdx = keys.indexOf('discipline');
  var roleIdx = keys.indexOf('role');

  var buckets = {};
  var push = function (key) {
    if (!buckets[key]) buckets[key] = [];
    buckets[key].push(Number(values[r][scoreIdx]));
  };

  for (var r = 0; r < values.length; r++) {
    var score = Number(values[r][scoreIdx]);
    if (!isFinite(score)) continue;
    var disc = String(values[r][discIdx] || '').trim();
    var role = String(values[r][roleIdx] || '').trim();
    var isAdmin = role !== 'Participant / Presenter' && role !== '';

    push('disc:' + disc);
    push('role:' + role);
    push(isAdmin ? 'group:admin' : 'group:participant');
    if (disc === 'Science & Technology') push(isAdmin ? 'cross:adminST' : 'cross:participantST');
    else if (disc === 'Social Science') push(isAdmin ? 'cross:adminSS' : 'cross:participantSS');
    else push(isAdmin ? 'cross:adminOther' : 'cross:participantOther');
  }

  var fill = function (target, key) {
    var arr = buckets[key] || [];
    if (!arr.length) return;
    var s = statsFor(arr);
    target.count = s.count;
    target.average = s.average;
    target.sd = s.sd;
    target.scores = arr;
  };

  fill(out.byDiscipline['Science & Technology'], 'disc:Science & Technology');
  fill(out.byDiscipline['Social Science'], 'disc:Social Science');
  fill(out.byDiscipline['Other / Interdisciplinary'], 'disc:Other / Interdisciplinary');
  fill(out.byRole['Participant / Presenter'], 'role:Participant / Presenter');
  fill(out.byRole['Conference administrator'], 'role:Conference administrator');
  fill(out.byRole['Secretariat committee'], 'role:Secretariat committee');
  fill(out.byRole['Reviewer / Evaluator'], 'role:Reviewer / Evaluator');
  fill(out.roleGroup.participant, 'group:participant');
  fill(out.roleGroup.admin, 'group:admin');
  fill(out.cross.participantST, 'cross:participantST');
  fill(out.cross.adminST, 'cross:adminST');
  fill(out.cross.participantSS, 'cross:participantSS');
  fill(out.cross.adminSS, 'cross:adminSS');
  fill(out.cross.participantOther, 'cross:participantOther');
  fill(out.cross.adminOther, 'cross:adminOther');

  return out;
}

/** Mean / SD helpers for an arbitrary array of scores. */
function statsFor(scores) {
  var n = scores.length;
  if (!n) return { count: 0, average: null, sd: null };
  var sum = scores.reduce(function (a, b) { return a + b; }, 0);
  var mean = sum / n;
  var variance = scores.reduce(function (a, b) { return a + Math.pow(b - mean, 2); }, 0) / (n > 1 ? n - 1 : 1);
  return {
    count: n,
    average: Math.round(mean * 10) / 10,
    sd: n > 1 ? Math.round(Math.sqrt(variance) * 10) / 10 : 0
  };
}

function countAtLeast(arr, min) { return arr.filter(function (v) { return v >= min; }).length; }
function countBelow(arr, max) { return arr.filter(function (v) { return v < max; }).length; }
function countBetween(arr, min, max) { return arr.filter(function (v) { return v >= min && v < max; }).length; }

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function jsonOut(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

function str(value, max) {
  if (value === undefined || value === null) return '';
  var s = String(value).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '').trim();
  return s.length > max ? s.slice(0, max) : s;
}

function clampInt(value, min, max) {
  var n = Math.round(Number(value));
  if (!isFinite(n)) return '';
  return Math.max(min, Math.min(max, n));
}

function parseDate(value) {
  var d = new Date(value);
  return isNaN(d.getTime()) ? new Date() : d;
}

function logEvent(message) {
  try {
    var ss = getSpreadsheet();
    var sheet = ss.getSheetByName(LOG_SHEET) || ss.insertSheet(LOG_SHEET);
    sheet.appendRow([new Date(), str(message, 2000)]);
  } catch (e) {
    // Logging must never break a submission.
  }
}

// ---------------------------------------------------------------------------
// Manual helpers (run from the editor)
// ---------------------------------------------------------------------------

/** Run once to verify the sheet is created and the summary tab renders. */
function setup() {
  var ss = getSpreadsheet();
  getOrCreateSheet(ss, RESPONSES_SHEET, COLUMNS.map(function (c) { return c.label; }));
  refreshSummary(ss);
  SpreadsheetApp.getActive().toast('SUS sheets are ready.', 'GDIID', 5);
}

/** Inserts a sample response so you can test the dashboard and summary. */
function insertSampleResponse() {
  var sample = {
    sus: [4, 2, 4, 2, 4, 1, 4, 2, 5, 2],
    participantId: 'GDIID-SUS-TEST-0001',
    submissionId: 'SUB-SAMPLE-' + new Date().getTime(),
    name: 'Sample Participant',
    email: 'sample@example.com',
    role: 'Participant / Presenter',
    device: 'Desktop / laptop',
    experience: 'First time using GDIID',
    modules: ['Sign in / registration', 'Payment proof upload'],
    recommend: 8,
    liked: 'Sign in was quick.',
    improve: 'The upload button was hard to find.',
    comments: '',
    durationSeconds: 180,
    formVersion: '1.0',
    submittedAt: new Date().toISOString()
  };
  doPost({ postData: { contents: JSON.stringify(sample) } });
}

/** Prints the current aggregate to the execution log. */
function showStats() {
  var stats = computeStats(getSpreadsheet());
  Logger.log(JSON.stringify(stats, null, 2));
}
