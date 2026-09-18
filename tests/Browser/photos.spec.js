import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync } from 'node:fs';

test.describe.configure({ retries: 0 });

test('real private photos, queue processing, primary selection, gallery and deletion', async ({ page, browser, baseURL }, testInfo) => {
    test.setTimeout(120000);
    const fixture = JSON.parse(readFileSync('.tools/listing-browser.json', 'utf8'))[testInfo.project.name];
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/ro/login');
    await page.getByLabel('Adresa de email', { exact: true }).fill(fixture.email);
    await page.getByLabel('Parolă', { exact: true }).fill(fixture.password);
    await page.getByRole('button', { name: 'Intră în cont', exact: true }).click();
    await page.goto('/ro/sell?category=calculatoare');
    await page.getByRole('button', { name: 'Folosește această categorie' }).click();
    await expect(page).toHaveURL(/edit\/photos$/);
    const root = page.url().replace('/edit/photos', '');
    await page.screenshot({ path: testInfo.outputPath('photos-empty.png'), fullPage: true });
    await page.getByLabel('Alege fotografiile').setInputFiles(['public/images/aproape-home.webp', 'tests/fixtures/avatar.png']);
    await page.getByRole('button', { name: 'Adaugă fotografiile', exact: true }).click();
    const cards = page.locator('.photo-item');
    await expect(cards).toHaveCount(2);
    await expect(page.locator('.photo-item[data-photo-state="ready"]')).toHaveCount(2, { timeout: 45000 });
    const first = await cards.nth(0).getAttribute('data-photo-id');
    const second = await cards.nth(1).getAttribute('data-photo-id');
    await expect(cards.nth(0).locator('img')).toHaveJSProperty('naturalWidth', 320);
    await cards.nth(1).getByRole('button', { name: /Alege ca principală/ }).click();
    await expect(cards.nth(0)).toHaveAttribute('data-photo-id', second);
    await cards.nth(0).getByRole('button', { name: /Mută mai târziu/ }).click();
    await expect(cards.nth(0)).toHaveAttribute('data-photo-id', first);
    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze()).violations).toEqual([]);
    const viewport = page.viewportSize();
    for (const width of [320, 390, 768, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Photo workspace fits '+width).toBe(true);
    }
    await page.setViewportSize(viewport);
    await page.screenshot({ path: testInfo.outputPath('photos-ready.png'), fullPage: true });

    const guest = await browser.newContext({ baseURL });
    expect((await guest.request.get('/media/listings/'+first+'/card')).status()).toBe(404);
    expect((await guest.request.get('/media/listings/'+first+'/source')).status()).toBe(404);
    await guest.close();

    // The real PHP multipart boundary must reject nine images, never silently truncate them.
    const token = await page.locator('[data-photo-upload] input[name="_token"]').inputValue();
    const version = await page.locator('[data-photo-upload] input[name="version"]').inputValue();
    const body = new FormData();
    body.append('_token', token);
    body.append('version', version);
    for (let index = 0; index < 9; index++) body.append('photos[]', new File([readFileSync('tests/fixtures/avatar.png')], 'ninth.png', { type: 'image/png' }));
    const rejected = await page.request.post(root+'/photos', { multipart: body, headers: { Accept: 'application/json' } });
    expect(rejected.status()).toBe(422);
    expect((await rejected.json()).errors.photos).toBeTruthy();
    await page.reload();
    await expect(cards).toHaveCount(2);

    await page.goto(root+'/preview');
    await expect.poll(() => page.locator('.gallery-main img').evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
    expect(await page.locator('.gallery-main img').evaluate(image => image.currentSrc)).toMatch(/\/media\/listings\/[^/]+\/(card|full)$/);
    await page.locator('.gallery-more summary').click();
    await expect(page.locator('.gallery-thumbnails img')).toHaveCount(1);
    await page.locator('.gallery-thumbnails img').scrollIntoViewIfNeeded();
    await expect.poll(() => page.locator('.gallery-thumbnails img').evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
    await page.screenshot({ path: testInfo.outputPath('photo-gallery.png'), fullPage: true });
    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze()).violations).toEqual([]);
    await page.goto(root+'/edit/photos');
    await cards.nth(1).getByRole('button', { name: /Șterge fotografia/ }).click();
    await expect(cards).toHaveCount(1);
    expect((await page.request.get('/media/listings/'+second+'/card')).status()).toBe(404);
    await page.getByRole('button', { name: 'Salvează și închide' }).click();
    const card = page.locator('.draft-card').filter({ has: page.locator('a[href*="'+root.replace(baseURL, '')+'/"]') });
    await expect(card.locator('.draft-card-photo img')).toHaveAttribute('src', /\/media\/listings\/[^/]+\/card$/);
    expect(errors).toEqual([]);
});
