/**
 * axe-core WCAG 2.1 A/AA sweep over the authenticated route surface.
 *
 * Companion to the visual baseline: same routes (routes.json), same Apache gate
 * server, same test user — but instead of pixels it runs axe-core and aggregates
 * violations by rule. Produces docs input + a machine-readable axe-results.json.
 *
 * This is an AUDIT tool (reports; changes nothing). Re-run after each accessibility
 * remediation tier to watch the counts fall. See docs/UX4G-Accessibility-Axe-Audit.md.
 *
 *   # Apache gate server must be up on :8080 (NOT `php artisan serve` — single-thread).
 *   E2E_BASE_URL=http://localhost:8080 E2E_USERNAME=... E2E_PASSWORD=... \
 *     node tests/e2e/visual/axe-sweep.js
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');

const HERE = __dirname;
const AXE = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const ROUTES = JSON.parse(fs.readFileSync(path.join(HERE, 'routes.json'), 'utf8')).routes;
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8080';
const OUT = path.join(HERE, 'axe-results.json');

(async () => {
  const user = process.env.E2E_USERNAME, pass = process.env.E2E_PASSWORD;
  if (!user || !pass) throw new Error('E2E_USERNAME / E2E_PASSWORD required');

  const b = await chromium.launch({ channel: 'chrome', headless: true });
  const p = await b.newPage({ viewport: { width: 1366, height: 768 } });
  await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
  await p.locator('input[name="username"]').first().fill(user);
  await p.locator('input[name="password"]').first().fill(pass);
  await Promise.all([p.waitForLoadState('networkidle').catch(() => {}), p.locator('button[type="submit"]').first().click()]);
  await p.waitForTimeout(1000);

  const rules = {};
  const perPage = {};
  let scanned = 0;

  for (const route of ROUTES) {
    try {
      await p.goto(BASE + route, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await p.waitForLoadState('networkidle').catch(() => {});
      await p.waitForTimeout(700);
      await p.evaluate(AXE);
      const res = await p.evaluate(async () => await window.axe.run(document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
        resultTypes: ['violations'],
      }));
      scanned++;
      let n = 0;
      for (const v of res.violations) {
        n += v.nodes.length;
        const r = rules[v.id] || (rules[v.id] = { impact: v.impact, help: v.help, wcag: v.tags.filter(t => /wcag\d/.test(t)).join(','), pages: new Set(), nodes: 0, samples: [] });
        r.pages.add(route); r.nodes += v.nodes.length;
        if (r.samples.length < 3 && v.nodes[0]) r.samples.push(v.nodes[0].target.join(' '));
      }
      perPage[route] = n;
      process.stdout.write(`  [${scanned}/${ROUTES.length}] ${route} — ${res.violations.length} rules / ${n} nodes\n`);
    } catch (e) {
      process.stdout.write(`  skip ${route} (${e.message.split('\n')[0]})\n`);
    }
  }

  const order = { critical: 0, serious: 1, moderate: 2, minor: 3 };
  const summary = Object.entries(rules).map(([id, r]) => ({ id, impact: r.impact, help: r.help, wcag: r.wcag, pages: r.pages.size, nodes: r.nodes, samples: r.samples }))
    .sort((a, b) => (order[a.impact] - order[b.impact]) || (b.nodes - a.nodes));

  fs.writeFileSync(OUT, JSON.stringify({ scanned, totalRules: summary.length, totalNodes: summary.reduce((a, r) => a + r.nodes, 0), rules: summary, perPage }, null, 2));
  console.log(`\n=== axe summary: ${scanned} pages, ${summary.length} rules, ${summary.reduce((a, r) => a + r.nodes, 0)} nodes -> ${OUT}`);
  for (const s of summary) console.log(`  [${(s.impact || '?').toUpperCase().padEnd(8)}] ${s.id.padEnd(28)} ${String(s.nodes).padStart(5)} / ${String(s.pages).padStart(2)}p  (${s.wcag})`);
  await b.close();
})();
