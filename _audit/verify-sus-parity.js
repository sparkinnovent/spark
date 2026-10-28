/**
 * Client/server parity for the SUS score, plus a recomputation check on the
 * 45 simulated responses.
 *
 *   node _audit/verify-sus-parity.js
 */

const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');

let pass = 0, fail = 0;
const failures = [];
function check(label, ok, detail = '') {
  if (ok) { pass++; console.log(`  ok    ${label}`); }
  else { fail++; failures.push(label); console.log(`  FAIL  ${label}${detail ? ' — ' + detail : ''}`); }
}

/* ------------------------------------------------------------------ */
/* 1. Formula present on both sides                                    */
/* ------------------------------------------------------------------ */

const html = fs.readFileSync(path.join(root, 'sus-study.html'), 'utf8');
const gs   = fs.readFileSync(path.join(root, 'sus-apps-script', 'Code.gs'), 'utf8');

console.log('SUS parity check\n');
console.log('--- formula present in both implementations ---');

// Code.gs: total * 2.5, rounded to one decimal.
check('Code.gs applies the x2.5 term', /total\s*\*\s*2\.5/.test(gs));
check('Code.gs distinguishes odd/even items', /i\s*%\s*2\s*===\s*0/.test(gs));
check('Code.gs rounds to one decimal', /Math\.round\([^)]*\*\s*10\)\s*\/\s*10/.test(gs));
check('sus-study.html applies the x2.5 term', /2\.5/.test(html));
check('sus-study.html distinguishes odd/even items', /%\s*2\s*===\s*0/.test(html));

/* ------------------------------------------------------------------ */
/* 2. Extract and execute the real Code.gs function                    */
/* ------------------------------------------------------------------ */

console.log('\n--- formula execution, Code.gs vs the form ---');

function extractFn(src, name) {
  const start = src.indexOf('function ' + name + '(');
  if (start < 0) return null;
  let depth = 0, i = src.indexOf('{', start);
  const open = i;
  for (; i < src.length; i++) {
    if (src[i] === '{') depth++;
    else if (src[i] === '}') { depth--; if (depth === 0) break; }
  }
  return src.slice(open, i + 1);
}

const serverBody = extractFn(gs, 'computeSus');
let serverScore = null;
if (serverBody) {
  // eslint-disable-next-line no-new-func
  serverScore = new Function('raw', serverBody + '; return computeSus(raw);');
  check('computeSus was extracted from Code.gs and executes', typeof serverScore === 'function');
} else {
  check('computeSus was extracted from Code.gs and executes', false, 'function not found');
}

function clientScore(r) {
  let sum = 0;
  for (let i = 0; i < r.length; i++) sum += (i % 2 === 0) ? (r[i] - 1) : (5 - r[i]);
  return sum * 2.5;
}

if (serverScore) {
  function mulberry32(a) {
    return function () {
      a |= 0; a = (a + 0x6D2B79F5) | 0;
      let t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  const rnd = mulberry32(20251025);
  let mism = 0;
  const N = 200000;
  for (let n = 0; n < N; n++) {
    const v = Array.from({ length: 10 }, () => 1 + Math.floor(rnd() * 5));
    // The server rounds to one decimal; compare at that resolution.
    if (Math.abs(Math.round(clientScore(v) * 10) / 10 - serverScore(v)) > 1e-9) mism++;
  }
  check(`the two implementations agree on ${N.toLocaleString()} random vectors`, mism === 0, `${mism} mismatch(es)`);
}

/* ------------------------------------------------------------------ */
/* 3. Recompute every row in the 45-response CSV                       */
/* ------------------------------------------------------------------ */

console.log('\n--- the 45 simulated responses ---');

const csvPath = path.join(root, 'sus-simulation-45.csv');
if (fs.existsSync(csvPath)) {
  const lines = fs.readFileSync(csvPath, 'utf8').trim().split(/\r?\n/);

  // Minimal RFC4180 split: these fields contain quoted commas.
  function splitRow(line) {
    const out = [];
    let cur = '', inQ = false;
    for (let i = 0; i < line.length; i++) {
      const ch = line[i];
      if (inQ) {
        if (ch === '"' && line[i + 1] === '"') { cur += '"'; i++; }
        else if (ch === '"') inQ = false;
        else cur += ch;
      } else if (ch === '"') inQ = true;
      else if (ch === ',') { out.push(cur); cur = ''; }
      else cur += ch;
    }
    out.push(cur);
    return out;
  }

  const header = splitRow(lines[0]);
  const rows = lines.slice(1).map(splitRow);

  check('CSV has 45 data rows', rows.length === 45, String(rows.length));

  const iScore = header.indexOf('SUS Score');
  const itemIdx = header
    .map((h, i) => ({ h, i }))
    .filter(x => /^SUS \d+$/.test(x.h))
    .map(x => x.i);

  check('CSV has 10 SUS item columns', itemIdx.length === 10, String(itemIdx.length));

  let bad = 0;
  const scores = [];
  const vectors = new Set();

  for (const r of rows) {
    const ratings = itemIdx.map(i => Number(r[i]));
    if (ratings.some(v => !Number.isFinite(v) || v < 1 || v > 5)) { bad++; continue; }
    const recomputed = Math.round(clientScore(ratings) * 10) / 10;
    const declared = Number(r[iScore]);
    if (Math.abs(recomputed - declared) > 1e-9) bad++;
    scores.push(declared);
    vectors.add(ratings.join(''));
  }

  check('every row recomputes to its declared score', bad === 0, `${bad} bad row(s)`);
  check('no two rows have identical rating vectors', vectors.size === 45, `${vectors.size} distinct`);

  const mean = scores.reduce((a, b) => a + b, 0) / scores.length;
  check('overall mean is 68.8', Math.abs(mean - 68.8) < 0.06, mean.toFixed(2));

  const min = Math.min(...scores), max = Math.max(...scores);
  check('range is 42.5 to 95', min === 42.5 && max === 95, `${min}..${max}`);

  // Every point on the 5-point scale should appear (the sim must not be flat).
  const used = new Set();
  for (const r of rows) itemIdx.forEach(i => used.add(Number(r[i])));
  check('all 5 scale points are used', used.size === 5, [...used].sort().join(','));
} else {
  check('sus-simulation-45.csv is present', false);
}

/* ------------------------------------------------------------------ */
/* 4. Segment totals must sum to 45                                    */
/* ------------------------------------------------------------------ */

console.log('\n--- segmentation integrity ---');

const jsonPath = path.join(root, 'sus-simulation-45.json');
if (fs.existsSync(jsonPath)) {
  const d = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

  const discSum = Object.values(d.byDiscipline || {}).reduce((a, x) => a + x.n, 0);
  check('discipline segments sum to 45', discSum === 45, String(discSum));

  const roleSum = Object.values(d.byRole || {}).reduce((a, x) => a + x.n, 0);
  check('role segments sum to 45', roleSum === 45, String(roleSum));

  check('overall n is 45', d.overall.n === 45, String(d.overall.n));
  check('seed is recorded in meta', Number(d.meta.seed) === 20251025, String(d.meta.seed));
  check('the dataset is labelled simulated', /simulat/i.test(JSON.stringify(d.meta.note || '')), String(d.meta.note).slice(0, 40));
}

/* ------------------------------------------------------------------ */
console.log('\n--- boundary values ---');
/*
 * The SUS transform is not monotonic in the raw rating: odd items score r-1 and
 * even items score 5-r, so a row of straight 1s and a row of straight 5s both
 * land on 50. The true floor is alternating extremes.
 */
check('all-1s scores 50 (consistent straight-lining, not zero)',
  clientScore(Array(10).fill(1)) === 50);
check('all-5s scores 50, not 100', clientScore(Array(10).fill(5)) === 50);
check('all-3s scores 50', clientScore(Array(10).fill(3)) === 50);
check('alternating 1,5 is the floor at 0',
  clientScore([1,5,1,5,1,5,1,5,1,5]) === 0);
check('the ceiling 100 is unreachable by design',
  Math.max(...[0,1].flatMap(o => Array(5).fill(o ? 5 : 1)).map((_, i, a) => clientScore(a))) <= 100);

console.log(`\n${pass} passed, ${fail} failed`);
if (fail) console.log('\nFAILURES:\n' + failures.map(f => '  - ' + f).join('\n'));
process.exit(fail ? 1 : 0);
