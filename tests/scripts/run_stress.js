#!/usr/bin/env node
'use strict';
/**
 * run_stress.js — Run the full Playwright suite N times with randomized env,
 * collect per-test failure frequencies, identify flaky tests, and print a
 * deterministic failure report.
 *
 * Usage:
 *   node tests/scripts/run_stress.js [--runs 20] [--spec 10_stress_fuzz]
 *
 * Output:
 *   - Per-run pass/fail summary (live)
 *   - Final aggregated failure matrix
 *   - Reproducible seeds for each failure
 *   - Root-cause grouping by error message pattern
 */

const { execSync, spawnSync } = require('child_process');
const path   = require('path');
const fs     = require('fs');
const crypto = require('crypto');

// ── CLI args ──────────────────────────────────────────────────────────────────
const args  = process.argv.slice(2);
const RUNS  = parseInt(getArg(args, '--runs',  '20'), 10);
const SPEC  = getArg(args, '--spec', '');           // '' = all specs
const ITERS = getArg(args, '--iters', '20');         // per-stress-test iterations
const OUT   = getArg(args, '--out',  'tests/scripts/stress_report.json');

function getArg(arr, flag, def) {
  const i = arr.indexOf(flag);
  return i >= 0 && arr[i + 1] ? arr[i + 1] : def;
}

// ── Paths ─────────────────────────────────────────────────────────────────────
const ROOT    = path.resolve(__dirname, '../..');
const TESTS   = path.join(ROOT, 'tests');
const RESULTS = path.join(TESTS, 'test-results');
const REPORT  = path.join(TESTS, 'playwright-report');

// ── State ─────────────────────────────────────────────────────────────────────
const runResults = [];   // [{ run, seed, passed, failed, skipped, tests: [{title, status, error}] }]

// ── Error pattern extractor ───────────────────────────────────────────────────
const ROOT_CAUSE_PATTERNS = [
  { pattern: /Timeout.*waiting for "ludo\.game\.dice_rolled"/,  label: 'roll-timeout'            },
  { pattern: /Timeout.*waiting for "ludo\.game\.token_moved"/,  label: 'move-timeout'            },
  { pattern: /Timeout.*waiting for "ludo\.game\.turn_started"/, label: 'turn-started-timeout'    },
  { pattern: /Timeout.*waiting for "ludo\.game\.result"/,       label: 'result-timeout'          },
  { pattern: /Timeout.*waiting for "ludo\.game\.state"/,        label: 'reconnect-state-timeout' },
  { pattern: /safety limit.*turns reached/,                     label: 'safety-limit-turns'      },
  { pattern: /duplicate result/i,                               label: 'duplicate-result'        },
  { pattern: /duplicate token_moved/i,                          label: 'duplicate-token-moved'   },
  { pattern: /stale nonce.*accepted/i,                          label: 'nonce-not-rejected'      },
  { pattern: /invalid nonce triggered roll/i,                   label: 'nonce-accepted'          },
  { pattern: /turn-order violations/i,                          label: 'turn-order-violation'    },
  { pattern: /game dead after reconnect storm/i,                label: 'reconnect-storm-crash'   },
  { pattern: /connect_error|ECONNREFUSED/i,                     label: 'server-unreachable'      },
];

function classifyError(msg) {
  if (!msg) return 'unknown';
  for (const { pattern, label } of ROOT_CAUSE_PATTERNS) {
    if (pattern.test(msg)) return label;
  }
  return 'other: ' + msg.slice(0, 80).replace(/\n/g, ' ');
}

// ── JSON result parser ────────────────────────────────────────────────────────
function parseResultsJson() {
  const jsonPath = path.join(REPORT, 'results.json');
  if (!fs.existsSync(jsonPath)) return null;
  try {
    return JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
  } catch { return null; }
}

// ── Run one Playwright pass ───────────────────────────────────────────────────
function runPlaywright(seed, run) {
  const specArg = SPEC
    ? `playwright/specs/${SPEC}.spec.js`
    : 'playwright/specs/';

  const env = {
    ...process.env,
    STRESS_SEED:       String(seed),
    STRESS_ITERATIONS: ITERS,
    STRESS_RUN:        String(run),
  };

  const result = spawnSync(
    'npx', ['playwright', 'test', specArg,
      '--reporter=json',
      '--output', RESULTS,
    ],
    {
      cwd:     TESTS,
      env,
      stdio:   'pipe',
      timeout: 30 * 60 * 1000,   // 30 min max per run
      shell:   true,
    }
  );

  // Parse JSON output from stdout
  let pwData = null;
  try {
    const stdout = result.stdout?.toString() || '';
    // Playwright JSON reporter writes the JSON to stdout
    const jsonMatch = stdout.match(/(\{[\s\S]*\})/);
    if (jsonMatch) pwData = JSON.parse(jsonMatch[1]);
  } catch { /* ignore parse errors */ }

  // Fallback: parse the results.json file
  if (!pwData) pwData = parseResultsJson();

  const tests = [];
  if (pwData?.suites) {
    extractTests(pwData.suites, tests);
  }

  const passed  = tests.filter(t => t.status === 'passed').length;
  const failed  = tests.filter(t => t.status === 'failed').length;
  const skipped = tests.filter(t => t.status === 'skipped').length;

  return { run, seed, passed, failed, skipped, tests, exitCode: result.status };
}

function extractTests(suites, out) {
  for (const suite of suites) {
    if (suite.specs) {
      for (const spec of suite.specs) {
        for (const test of (spec.tests || [])) {
          const lastResult = test.results?.[test.results.length - 1];
          out.push({
            title:  spec.title,
            status: lastResult?.status ?? 'unknown',
            error:  lastResult?.error?.message ?? '',
          });
        }
      }
    }
    if (suite.suites) extractTests(suite.suites, out);
  }
}

// ── Main ──────────────────────────────────────────────────────────────────────
async function main() {
  console.log(`\n${'═'.repeat(72)}`);
  console.log(`  Ludo Multiplayer Stress Runner`);
  console.log(`  Runs: ${RUNS}  |  Spec: ${SPEC || 'all'}  |  Iters/stress-test: ${ITERS}`);
  console.log(`${'═'.repeat(72)}\n`);

  const startTime = Date.now();

  for (let run = 1; run <= RUNS; run++) {
    const seed = crypto.randomBytes(4).readUInt32BE(0);
    process.stdout.write(`Run ${String(run).padStart(2)}/${RUNS}  seed=${seed.toString(16).padStart(8, '0')}  `);

    const r = runPlaywright(seed, run);
    runResults.push(r);

    const icon  = r.failed > 0 ? '✗' : '✓';
    const color = r.failed > 0 ? '\x1b[31m' : '\x1b[32m';
    console.log(
      `${color}${icon}\x1b[0m  ` +
      `passed=${r.passed}  failed=${r.failed}  skipped=${r.skipped}  ` +
      `exit=${r.exitCode}  ${((Date.now() - startTime) / 1000 / run).toFixed(1)}s/run`
    );
  }

  // ── Aggregate ───────────────────────────────────────────────────────────────
  const totalRuns   = runResults.length;
  const totalFailed = runResults.filter(r => r.failed > 0).length;

  // Per-test failure map: title → { count, rootCauses: Map<label,count>, seeds: [] }
  const testMap = new Map();
  for (const run of runResults) {
    for (const t of run.tests) {
      if (t.status !== 'failed') continue;
      if (!testMap.has(t.title)) {
        testMap.set(t.title, { count: 0, rootCauses: new Map(), seeds: [], errors: [] });
      }
      const entry = testMap.get(t.title);
      entry.count++;
      entry.seeds.push(run.seed.toString(16).padStart(8, '0'));
      const rc = classifyError(t.error);
      entry.rootCauses.set(rc, (entry.rootCauses.get(rc) || 0) + 1);
      if (entry.errors.length < 3) entry.errors.push(t.error?.slice(0, 400) || '');
    }
  }

  // ── Print report ────────────────────────────────────────────────────────────
  console.log(`\n${'═'.repeat(72)}`);
  console.log('  STRESS REPORT');
  console.log(`${'═'.repeat(72)}\n`);
  console.log(`  Total runs:        ${totalRuns}`);
  console.log(`  Runs with failure: ${totalFailed} / ${totalRuns}  (${(100 * totalFailed / totalRuns).toFixed(1)}%)`);
  console.log(`  Total elapsed:     ${((Date.now() - startTime) / 1000 / 60).toFixed(1)} min\n`);

  if (testMap.size === 0) {
    console.log('  \x1b[32m✓ All tests passed across all runs\x1b[0m\n');
  } else {
    console.log(`  \x1b[31m✗ ${testMap.size} test(s) had failures:\x1b[0m\n`);

    const sorted = [...testMap.entries()].sort((a, b) => b[1].count - a[1].count);
    for (const [title, entry] of sorted) {
      const freq = (100 * entry.count / totalRuns).toFixed(1);
      console.log(`  ┌── ${title}`);
      console.log(`  │   Failures: ${entry.count}/${totalRuns} (${freq}%)`);
      console.log(`  │   Seeds:    ${entry.seeds.slice(0, 5).join(', ')}${entry.seeds.length > 5 ? '...' : ''}`);

      const rcSorted = [...entry.rootCauses.entries()].sort((a, b) => b[1] - a[1]);
      for (const [rc, cnt] of rcSorted) {
        console.log(`  │   Root cause [${rc}]: ${cnt}x`);
      }
      if (entry.errors[0]) {
        const snippet = entry.errors[0].split('\n')[0].slice(0, 120);
        console.log(`  │   Sample error: ${snippet}`);
      }
      console.log('  └──\n');
    }
  }

  // ── Deterministic fix recommendations ────────────────────────────────────────
  const fixMap = {
    'roll-timeout':            'Server not receiving roll_dice: check nonce, roomId, rate-limit, socket identity binding',
    'move-timeout':            'Server not receiving move_token: check roll_nonce, has_moves guard, socket binding',
    'turn-started-timeout':    'turn_started not arriving: check _advanceTurn / _startTurn, turn timer logic',
    'result-timeout':          'result not arriving after is_win: check _doMove result broadcast, playFullGame waitFor race',
    'reconnect-state-timeout': 'ludo.game.state not sent on reconnect: check session.reconnect handler room lookup',
    'safety-limit-turns':      'Game exceeds turn limit: increase maxTurns or check for stuck turn loop',
    'duplicate-result':        'result broadcast multiple times: check _gameState.over guard in result emission',
    'duplicate-token-moved':   'token_moved replayed: check replay attack rejection (nonce already consumed)',
    'nonce-not-rejected':      'Stale nonce accepted after reconnect: check _acValidateTurnNonce rotation on reconnect',
    'nonce-accepted':          'Invalid nonce not rejected: check _acValidateTurnNonce strict comparison',
    'turn-order-violation':    'Wrong seat gets turn after non-6 move: check _advanceTurn extra_turn guard',
    'reconnect-storm-crash':   'Game dies after rapid reconnects: check room cleanup / session leak on rapid disconnect',
    'server-unreachable':      'Server not running or wrong port: start node/server.js on port 3002',
  };

  if (testMap.size > 0) {
    console.log('  DETERMINISTIC FIX RECOMMENDATIONS');
    console.log(`  ${'─'.repeat(68)}\n`);
    const seenRc = new Set();
    for (const [, entry] of testMap) {
      for (const [rc] of entry.rootCauses) {
        if (!seenRc.has(rc) && fixMap[rc]) {
          seenRc.add(rc);
          console.log(`  [${rc}]`);
          console.log(`    ${fixMap[rc]}\n`);
        }
      }
    }
  }

  // ── Write JSON report ───────────────────────────────────────────────────────
  const reportData = {
    timestamp:    new Date().toISOString(),
    totalRuns,
    totalFailed,
    failureRate:  totalFailed / totalRuns,
    tests:        Object.fromEntries(
      [...testMap.entries()].map(([title, e]) => [title, {
        failCount:  e.count,
        failRate:   e.count / totalRuns,
        rootCauses: Object.fromEntries(e.rootCauses),
        seeds:      e.seeds,
        sampleErrors: e.errors,
      }])
    ),
    runs: runResults.map(r => ({
      run:   r.run,
      seed:  r.seed.toString(16),
      passed: r.passed,
      failed: r.failed,
    })),
  };

  const outPath = path.resolve(ROOT, OUT);
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, JSON.stringify(reportData, null, 2));
  console.log(`\n  Report written to: ${outPath}\n`);

  process.exit(totalFailed > 0 ? 1 : 0);
}

main().catch(e => {
  console.error(e);
  process.exit(2);
});
