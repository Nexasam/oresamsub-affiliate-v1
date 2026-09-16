const { chromium } = require('/private/tmp/oresamsub-video-tools/node_modules/playwright-core');
(async () => {
  const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: true });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 });
  await page.goto('file://' + __dirname + '/cards.html');
  await page.locator('#intro').screenshot({ path: __dirname + '/intro.png' });
  await page.locator('#outro').screenshot({ path: __dirname + '/outro.png' });
  await browser.close();
})();
