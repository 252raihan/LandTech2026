const { chromium } = require('playwright');

const paths = ['/dashboard', '/saga', '/sync-webhook', '/crypto-verify', '/plot-lock',
  '/ekyc-biometric', '/bank-lien', '/ai-ocr-conflict', '/faraz-calculator', '/gis-map',
  '/risk-scoring', '/audit-ledger'];

(async () => {
  const browser = await chromium.launch({
    executablePath: 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  });
  const ctx = await browser.newContext();
  let fail = 0;
  for (const p of paths) {
    const page = await ctx.newPage();
    const errors = [];
    const failed = [];
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
    page.on('pageerror', e => errors.push('PAGEERROR: ' + e.message));
    page.on('requestfailed', r => failed.push(r.url().slice(0, 90) + ' :: ' + (r.failure()?.errorText || '')));
    await page.goto('http://127.0.0.1:8000' + p, { waitUntil: 'networkidle', timeout: 30000 }).catch(e => errors.push('NAV: ' + e.message));
    await page.waitForTimeout(1200);
    const title = await page.title();
    const badge = await page.locator('text=FRONTEND DEMO PROTOTYPE').count();
    const h2 = await page.locator('header h2').first().textContent().catch(() => '?');
    const status = (errors.length || failed.length) ? 'ISSUES' : 'OK';
    if (status === 'ISSUES') fail++;
    console.log(`${status.padEnd(6)} ${p.padEnd(18)} title="${title}" h2="${(h2 || '').trim()}" disclaimer=${badge}`);
    errors.forEach(e => console.log('        console-error: ' + e.slice(0, 160)));
    failed.forEach(f => console.log('        request-failed: ' + f));
    await page.close();
  }
  await browser.close();
  console.log(`\n${paths.length - fail}/${paths.length} pages clean`);
})();
