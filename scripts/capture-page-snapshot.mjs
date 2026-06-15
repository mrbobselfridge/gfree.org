import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { dirname } from 'node:path';

const args = Object.fromEntries(
  process.argv
    .slice(2)
    .map((argument) => {
      const [key, ...value] = argument.replace(/^--/, '').split('=');

      return [key, value.join('=')];
    }),
);

const url = args.url;
const output = args.output;
const width = Number.parseInt(args.width ?? '1440', 10);
const height = Number.parseInt(args.height ?? '1000', 10);
const timeout = Number.parseInt(args.timeout ?? '60000', 10);

if (!url || !output) {
  console.error('Usage: node scripts/capture-page-snapshot.mjs --url=<url> --output=<path> [--width=1440] [--height=1000]');
  process.exit(1);
}

let browser;

try {
  await mkdir(dirname(output), { recursive: true });

  browser = await chromium.launch({ headless: true });

  const page = await browser.newPage({
    viewport: { width, height },
    deviceScaleFactor: 1,
  });

  await page.goto(url, {
    waitUntil: 'networkidle',
    timeout,
  });

  await page.evaluate(async () => {
    if ('fonts' in document) {
      await document.fonts.ready;
    }

    await Promise.all(
      Array.from(document.images).map((image) => {
        if (image.complete) {
          return Promise.resolve();
        }

        return new Promise((resolve) => {
          image.addEventListener('load', resolve, { once: true });
          image.addEventListener('error', resolve, { once: true });
        });
      }),
    );
  });

  await page.screenshot({
    path: output,
    fullPage: true,
    type: 'png',
  });
} catch (error) {
  const message = error instanceof Error ? error.message : String(error);

  console.error(`Page snapshot capture failed: ${message}`);
  console.error('If Chromium is not installed, run: npx playwright install chromium');
  console.error('If Chromium cannot launch because shared libraries are missing, run: npx playwright install-deps chromium');
  process.exitCode = 1;
} finally {
  if (browser) {
    await browser.close();
  }
}
