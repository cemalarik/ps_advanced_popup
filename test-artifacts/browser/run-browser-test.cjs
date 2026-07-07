const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const playwrightModule = process.env.PLAYWRIGHT_MODULE || 'playwright';
const { chromium } = require(playwrightModule);

const baseUrl = process.env.PS_BASE_URL || 'http://localhost:8080';
const storefrontUrl = process.env.PS_STOREFRONT_URL || `${baseUrl}/tr/`;
const adminToken = process.env.ADMIN_SMART_POPUP_TOKEN;
const adminEmail = process.env.PS_ADMIN_EMAIL || 'admin@prestashop.local';
const adminPassword = process.env.PS_ADMIN_PASSWORD || 'Admin123!';
const outDir = __dirname;

if (!adminToken) {
  throw new Error('ADMIN_SMART_POPUP_TOKEN is required.');
}

const adminRoot = `${baseUrl}/admin_dev/`;
const moduleUrl = `${adminRoot}index.php?controller=AdminSmartPopup&token=${adminToken}`;
const results = {
  screenshots: {},
  checks: [],
  consoleErrors: [],
  popupId: 0,
  eventCounts: {},
};

function record(name, pass, details) {
  const item = { name, pass: Boolean(pass), details: details || '' };
  results.checks.push(item);
  if (!item.pass) {
    throw new Error(`${name}${details ? `: ${details}` : ''}`);
  }
}

function mysql(sql) {
  return execFileSync('docker', [
    'exec',
    'prestashop_mysql',
    'mysql',
    '-uprestashop',
    '-pprestashop_password',
    'prestashop',
    '-N',
    '-e',
    sql,
  ], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}

function cleanupBrowserTestPopups() {
  mysql(`
    DELETE vl FROM ps_smart_popup_variant_lang vl
    INNER JOIN ps_smart_popup_variant v ON v.id_variant = vl.id_variant
    INNER JOIN ps_smart_popup p ON p.id_popup = v.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE v FROM ps_smart_popup_variant v
    INNER JOIN ps_smart_popup p ON p.id_popup = v.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE t FROM ps_smart_popup_targeting t
    INNER JOIN ps_smart_popup p ON p.id_popup = t.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE l FROM ps_smart_popup_lang l
    INNER JOIN ps_smart_popup p ON p.id_popup = l.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE e FROM ps_smart_popup_event e
    INNER JOIN ps_smart_popup p ON p.id_popup = e.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE s FROM ps_smart_popup_stats_daily s
    INNER JOIN ps_smart_popup p ON p.id_popup = s.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE sub FROM ps_smart_popup_subscriber sub
    INNER JOIN ps_smart_popup p ON p.id_popup = sub.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE ce FROM ps_smart_popup_coupon_event ce
    INNER JOIN ps_smart_popup p ON p.id_popup = ce.id_popup
    WHERE p.internal_name LIKE 'Browser Test%';
    DELETE FROM ps_smart_popup WHERE internal_name LIKE 'Browser Test%';
  `);
}

async function screenshot(page, key, fullPage = false) {
  const file = path.join(outDir, `${key}.png`);
  await page.screenshot({ path: file, fullPage });
  results.screenshots[key] = file;
}

async function loginAdmin(page) {
  await page.goto(adminRoot, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle').catch(() => {});

  const emailInput = page.locator('input[name="email"], input[type="email"]').first();
  if (await emailInput.count()) {
    await emailInput.fill(adminEmail);
    const passwordInput = page.locator('input[name="passwd"], input[type="password"]').first();
    await passwordInput.fill(adminPassword);
    const submit = page.locator('button[type="submit"], input[type="submit"]').first();
    await Promise.all([
      page.waitForURL((url) => !url.toString().includes('AdminLogin'), { timeout: 20000 }).catch(() => {}),
      submit.click(),
    ]);
    await page.waitForLoadState('networkidle').catch(() => {});
  }
}

async function gotoModule(page, suffix = '') {
  await page.goto(`${moduleUrl}${suffix}`, { waitUntil: 'domcontentloaded' }).catch((error) => {
    if (!String(error.message || error).includes('ERR_ABORTED')) {
      throw error;
    }
  });
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.locator('.aps-admin').waitFor({ state: 'visible', timeout: 20000 });
}

async function createPopupFromAdmin(page) {
  await gotoModule(page);
  record('Admin dashboard renders', await page.locator('.aps-admin .aps-metrics').isVisible());
  await screenshot(page, '01-admin-dashboard');

  await gotoModule(page, '&aps_view=add&template_key=exit_coupon');
  record('Admin editor renders', await page.locator('#aps-editor-form').isVisible());

  await page.locator('input[name="internal_name"]').fill('Browser Test Coupon');

  await page.locator('.aps-step[data-step="2"]').click();
  await page.locator('input[name="title_1"]').fill('Private 15% code');
  await page.locator('input[name="subtitle_1"]').fill('Use this before checkout.');
  await page.locator('textarea[name="content_1"]').fill('A focused test popup with A/B variants, coupon copy and analytics.');
  await page.locator('input[name="coupon_code_1"]').fill('BROWSER15');
  await page.locator('input[name="cta_text_1"]').fill('Copy code');

  await page.locator('.aps-step[data-step="3"]').click();
  await page.locator('select[name="layout"]').selectOption('compact_coupon');
  await page.locator('input[name="width"]').fill('540');
  await page.locator('input[name="border_radius"]').fill('8');
  await page.locator('input[name="overlay_opacity"]').fill('0.55');
  await page.locator('select[name="mobile_behavior"]').selectOption('bottom_sheet');
  const active = page.locator('input[name="active"]');
  if (!(await active.isChecked())) {
    await active.check();
  }

  await page.locator('.aps-step[data-step="4"]').click();
  await page.locator('select[name="trigger_type"]').selectOption('load');
  await page.locator('input[name="trigger_value"]').fill('1');
  await page.locator('input[name="frequency_days"]').fill('0');

  await page.locator('.aps-step[data-step="5"]').click();
  const ab = page.locator('input[name="ab_test_enabled"]');
  if (!(await ab.isChecked())) {
    await ab.check();
  }
  await page.locator('input[name="variant_a_traffic"]').fill('50');
  await page.locator('input[name="variant_a_title_1"]').fill('Private 15% code');
  await page.locator('input[name="variant_b_title_1"]').fill('Today-only 20% code');
  await page.locator('input[name="variant_a_coupon_code_1"]').fill('BROWSER15');
  await page.locator('input[name="variant_b_coupon_code_1"]').fill('BROWSER20');
  await page.locator('textarea[name="variant_a_content_1"]').fill('Variant A keeps the offer restrained.');
  await page.locator('textarea[name="variant_b_content_1"]').fill('Variant B tests a stronger discount headline.');
  await screenshot(page, '02-admin-editor-preview');

  let saveStatus = 0;
  await Promise.all([
    page.waitForResponse((response) => (
      response.url().includes('controller=AdminSmartPopup')
      && response.request().method() === 'POST'
    ), { timeout: 20000 }).then((response) => {
      saveStatus = response.status();
    }).catch(() => {}),
    page.waitForLoadState('networkidle').catch(() => {}),
    page.locator('#aps-editor-form button.btn-primary[type="submit"]').click(),
  ]);
  results.saveStatus = saveStatus;
  try {
    await page.locator('.aps-admin').waitFor({ state: 'visible', timeout: 20000 });
  } catch (error) {
    await screenshot(page, '99-admin-save-failure', true).catch(() => {});
    results.saveFailure = {
      url: page.url(),
      title: await page.title().catch(() => ''),
      body: await page.locator('body').innerText({ timeout: 3000 }).catch(() => ''),
    };
    throw error;
  }

  const alerts = await page.locator('.alert.alert-danger, .alert-danger').allTextContents();
  record('Popup save has no admin error', alerts.length === 0, alerts.join(' | '));
  record('Dashboard shows saved popup', await page.getByText('Browser Test Coupon').first().isVisible());
  await screenshot(page, '03-admin-dashboard-created');

  const idText = mysql("SELECT id_popup FROM ps_smart_popup WHERE internal_name = 'Browser Test Coupon' ORDER BY id_popup DESC LIMIT 1;");
  const id = Number(idText.replace(/[^0-9]/g, ''));
  record('Saved popup id exists', id > 0, idText);
  results.popupId = id;
  return id;
}

async function waitForPopup(page) {
  await page.waitForFunction(() => {
    const overlay = document.querySelector('.smart-popup-overlay');
    return overlay && getComputedStyle(overlay).display !== 'none';
  }, null, { timeout: 10000 });
}

async function testStorefrontDesktop(browser, popupId) {
  const context = await browser.newContext({
    viewport: { width: 1366, height: 900 },
    permissions: ['clipboard-read', 'clipboard-write'],
  });
  await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: baseUrl });
  const page = await context.newPage();
  page.on('console', (msg) => {
    if (msg.type() === 'error') results.consoleErrors.push(msg.text());
  });
  page.on('pageerror', (error) => results.consoleErrors.push(error.message));

  await page.goto(`${storefrontUrl}?aps_browser_desktop=${Date.now()}`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle').catch(() => {});
  await waitForPopup(page);
  await page.waitForTimeout(300);
  await page.waitForFunction(() => {
    const dialog = document.querySelector('.smart-popup-container');
    return dialog && dialog.contains(document.activeElement);
  }, null, { timeout: 1500 }).catch(() => {});

  const accessibility = await page.evaluate(() => {
    const overlay = document.querySelector('.smart-popup-overlay');
    const dialog = overlay && overlay.querySelector('.smart-popup-container');
    return {
      overlayVisible: overlay ? getComputedStyle(overlay).display : '',
      role: dialog ? dialog.getAttribute('role') : '',
      ariaModal: dialog ? dialog.getAttribute('aria-modal') : '',
      labelledBy: dialog ? dialog.getAttribute('aria-labelledby') : '',
      focusInside: dialog ? dialog.contains(document.activeElement) : false,
      selectedVariant: overlay ? overlay.getAttribute('data-selected-variant') : '',
    };
  });
  record('Desktop popup is visible', accessibility.overlayVisible === 'flex');
  record('Dialog role is accessible', accessibility.role === 'dialog');
  record('Dialog is modal', accessibility.ariaModal === 'true');
  record('Dialog has title label', Boolean(accessibility.labelledBy));
  record('Focus starts inside popup', accessibility.focusInside);
  record('A/B variant selected', Number(accessibility.selectedVariant) > 0, accessibility.selectedVariant);
  await screenshot(page, '04-front-desktop-popup');

  const copyButton = page.locator('.smart-popup-variant:not([hidden]) .smart-popup-copy-coupon');
  await copyButton.click();
  await page.waitForFunction(() => {
    const button = document.querySelector('.smart-popup-variant:not([hidden]) .smart-popup-copy-coupon');
    return button && /Copied|Copy failed/.test(button.textContent || '');
  }, null, { timeout: 5000 });
  await screenshot(page, '05-front-desktop-copied');

  await page.keyboard.press('Escape');
  await page.waitForFunction(() => {
    const overlay = document.querySelector('.smart-popup-overlay');
    return overlay && getComputedStyle(overlay).display === 'none';
  }, null, { timeout: 5000 });
  record('ESC closes popup', true);
  await context.close();
}

async function testStorefrontMobile(browser) {
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
  });
  const page = await context.newPage();
  page.on('console', (msg) => {
    if (msg.type() === 'error') results.consoleErrors.push(msg.text());
  });
  page.on('pageerror', (error) => results.consoleErrors.push(error.message));

  await page.goto(`${storefrontUrl}?aps_browser_mobile=${Date.now()}`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle').catch(() => {});
  await waitForPopup(page);
  await page.waitForTimeout(300);
  const mobileState = await page.evaluate(() => {
    const overlay = document.querySelector('.smart-popup-overlay');
    const dialog = overlay && overlay.querySelector('.smart-popup-container');
    const overlayStyle = overlay ? getComputedStyle(overlay) : null;
    const dialogStyle = dialog ? getComputedStyle(dialog) : null;
    return {
      device: window.smartPopupData ? window.smartPopupData.device : '',
      alignItems: overlayStyle ? overlayStyle.alignItems : '',
      width: dialog ? Math.round(dialog.getBoundingClientRect().width) : 0,
      radius: dialogStyle ? dialogStyle.borderTopLeftRadius : '',
    };
  });
  record('Mobile device detected', mobileState.device === 'mobile', JSON.stringify(mobileState));
  record('Mobile popup uses bottom alignment', mobileState.alignItems === 'flex-end', JSON.stringify(mobileState));
  record('Mobile popup fits viewport', mobileState.width <= 390, JSON.stringify(mobileState));
  await screenshot(page, '06-front-mobile-bottom-sheet');
  await context.close();
}

async function screenshotStats(page, popupId) {
  await gotoModule(page, `&aps_view=stats&id_popup=${popupId}`);
  record('Stats page renders', await page.locator('#apsStatsChart, .aps-stats').first().isVisible());
  await screenshot(page, '07-admin-stats');
}

function collectEventCounts(popupId) {
  const rows = mysql(`
    SELECT event_type, COUNT(*)
    FROM ps_smart_popup_event
    WHERE id_popup = ${Number(popupId)}
    GROUP BY event_type
    ORDER BY event_type;
  `);
  const counts = {};
  rows.split(/\r?\n/).filter(Boolean).forEach((line) => {
    const parts = line.split(/\s+/);
    counts[parts[0]] = Number(parts[1] || 0);
  });
  results.eventCounts = counts;
  record('Impression events tracked', (counts.impression || 0) >= 2, JSON.stringify(counts));
  record('Coupon copy event tracked', (counts.coupon_copy || 0) >= 1, JSON.stringify(counts));
  const couponRows = mysql(`SELECT COUNT(*) FROM ps_smart_popup_coupon_event WHERE id_popup = ${Number(popupId)};`);
  record('Coupon mapping event stored', Number(couponRows.replace(/[^0-9]/g, '')) >= 1, couponRows);
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  cleanupBrowserTestPopups();

  const browser = await chromium.launch({ headless: true });
  const adminContext = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const adminPage = await adminContext.newPage();
  adminPage.on('console', (msg) => {
    if (msg.type() === 'error') results.consoleErrors.push(msg.text());
  });
  adminPage.on('pageerror', (error) => results.consoleErrors.push(error.message));

  try {
    await loginAdmin(adminPage);
    const popupId = await createPopupFromAdmin(adminPage);
    await testStorefrontDesktop(browser, popupId);
    await testStorefrontMobile(browser);
    await screenshotStats(adminPage, popupId);
    collectEventCounts(popupId);
    record('No browser console/page errors', results.consoleErrors.length === 0, results.consoleErrors.join(' | '));
  } finally {
    await adminContext.close().catch(() => {});
    await browser.close().catch(() => {});
    fs.writeFileSync(path.join(outDir, 'browser-test-results.json'), JSON.stringify(results, null, 2));
  }
})();
