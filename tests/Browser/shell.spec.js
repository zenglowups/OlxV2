import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('localized shell, real navigation and loaded assets', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/ro');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ro');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Lucruri bune.');
    await expect(page.locator('.home-illustration')).toHaveJSProperty('naturalWidth', 1536);
    await page.screenshot({ path: testInfo.outputPath('shell.png'), fullPage: true });
    await page.getByRole('link', { name: 'Descoperă Aproape' }).click();
    await expect(page).toHaveURL(/#principii$/);
    await page.getByRole('link', { name: 'EN', exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Good things.');
    await page.getByRole('link', { name: 'RO', exact: true }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'ro');
    expect(errors).toEqual([]);
});

test('keyboard skip link and accessible content', async ({ page }) => {
    await page.goto('/ro');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Sari la conținut' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('#main')).toBeFocused();
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
    expect(results.violations).toEqual([]);
});

test('layout fits small phones through desktop', async ({ page }) => {
    for (const width of [320, 360, 390, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto('/ro');
        const fits = await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
        expect(fits, 'No horizontal overflow at '+width).toBe(true);
    }
});

test('mobile menu works with keyboard and closes after navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/ro');
    const menu = page.locator('.mobile-menu');
    await menu.locator('summary').focus();
    await page.keyboard.press('Enter');
    await expect(menu).toHaveAttribute('open', '');
    await page.keyboard.press('Escape');
    await expect(menu).not.toHaveAttribute('open', '');
    await expect(menu.locator('summary')).toBeFocused();
    await menu.locator('summary').click();
    await menu.getByRole('link', { name: 'Cum încep' }).click();
    await expect(menu).not.toHaveAttribute('open', '');
    await expect(page).toHaveURL(/#principii$/);
});

test('unknown page has a useful recovery link', async ({ page }) => {
    const response = await page.goto('/not-a-page');
    expect(response.status()).toBe(404);
    await page.getByRole('link', { name: 'Înapoi la început' }).click();
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Lucruri bune.');
});
