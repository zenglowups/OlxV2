import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { randomUUID } from 'node:crypto';
import { appendFileSync, mkdirSync } from 'node:fs';

const mailpit = process.env.MAILPIT_URL || 'http://mailpit:8025';

async function emailLink(request, email, path) {
    let found;
    await expect.poll(async () => {
        const response = await request.get(mailpit + '/api/v1/search', { params: { query: 'to:' + email } });
        expect(response.ok()).toBeTruthy();
        const result = await response.json();
        for (const message of result.messages || []) {
            const detail = await (await request.get(mailpit + '/api/v1/message/' + message.ID)).json();
            const links = Array.from((detail.HTML || '').matchAll(/href="([^"]+)"/g), m => m[1].replaceAll('&amp;', '&'));
            found = links.find(link => link.includes(path));
            if (found) return true;
        }
        return false;
    }, { timeout: 45000, intervals: [500, 1000, 2000] }).toBe(true);
    const url = new URL(found);
    expect(['localhost', '127.0.0.1', 'web']).toContain(url.hostname);
    // Signatures bind the path/query and authenticated user, independently of the local hostname.
    return url.pathname + url.search;
}

test('registration, real queued email, verification, reset, session revocation and login', async ({ page, request, browser, baseURL }, testInfo) => {
    test.setTimeout(120000);
    const id = randomUUID();
    const email = 'qa-auth-' + id + '@example.test';
    const name = 'QA ' + id;
    const password = 'A local test phrase ' + id;
    const newPassword = 'A changed local phrase ' + id;
    mkdirSync('.tools', { recursive: true });
    appendFileSync('.tools/qa-auth-users.jsonl', JSON.stringify({email, name}) + '\n');

    await page.goto('/ro/register');
    await page.getByLabel('Numele afișat', {exact:true}).fill(name);
    await page.getByLabel('Adresa de email', {exact:true}).fill(email);
    await page.getByLabel('Parolă', {exact:true}).fill(password);
    await page.getByLabel('Confirmă parola', {exact:true}).fill(password);
    await page.getByRole('button', {name:'Creează un cont', exact:true}).click();
    await expect(page).toHaveURL(/\/ro\/email\/verify$/);
    await expect(page.getByRole('heading', {level:1})).toHaveText('Confirmă adresa de email');
    const verification = await emailLink(request, email, '/email/verify/');
    await page.goto(verification);
    await expect(page).toHaveURL(/\/ro\/account$/);
    await expect(page.getByRole('heading', {level:1})).toHaveText(name);
    await expect(page.getByText('Email verificat', {exact:true})).toBeVisible();
    expect((await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze()).violations).toEqual([]);
    await page.screenshot({path:testInfo.outputPath('account.png'),fullPage:true});

    const second = await browser.newContext({baseURL});
    const other = await second.newPage();
    await other.goto('/ro/login');
    await other.getByLabel('Adresa de email', {exact:true}).fill(email);
    await other.getByLabel('Parolă', {exact:true}).fill(password);
    await other.getByRole('button', {name:'Intră în cont',exact:true}).click();
    await expect(other).toHaveURL(/\/ro\/account$/);
    await page.getByRole('button', {name:'Ieși din cont',exact:true}).click();
    await page.goto('/ro/forgot-password');
    await page.getByLabel('Adresa de email', {exact:true}).fill(email);
    await page.getByRole('button', {name:'Trimite linkul',exact:true}).click();
    await expect(page.getByRole('status')).toContainText('Dacă adresa');
    const reset = await emailLink(request, email, '/reset-password');
    await page.goto(reset);
    await page.getByLabel('Parolă', {exact:true}).fill(newPassword);
    await page.getByLabel('Confirmă parola', {exact:true}).fill(newPassword);
    await page.getByRole('button', {name:'Schimbă parola',exact:true}).click();
    await expect(page).toHaveURL(/\/ro\/login$/);
    await other.goto('/ro/account');
    await expect(other).toHaveURL(/\/ro\/login$/);
    await second.close();
    await page.getByLabel('Adresa de email', {exact:true}).fill(email);
    await page.getByLabel('Parolă', {exact:true}).fill(newPassword);
    await page.getByRole('button', {name:'Intră în cont',exact:true}).click();
    await expect(page).toHaveURL(/\/ro\/account$/);
    await page.getByLabel('Parolă', {exact:true}).fill(newPassword);
    await page.getByRole('button', {name:'Închide toate sesiunile',exact:true}).click();
    await expect(page).toHaveURL(/\/ro\/login$/);
    await expect(page.getByRole('status')).toContainText('Toate sesiunile');
});

test('auth screens are accessible, responsive and switch language within the same form', async ({page}, testInfo) => {
    for (const path of ['/ro/login','/ro/register','/ro/forgot-password']) {
        await page.goto(path);
        expect((await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze()).violations).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }
    await page.goto('/ro/register');
    await page.screenshot({path:testInfo.outputPath('registration.png'),fullPage:true});
    await page.getByRole('link', {name:'EN',exact:true}).click();
    await expect(page).toHaveURL(/\/en\/register$/);
    await expect(page.getByRole('heading', {level:1})).toHaveText('One account, a good beginning.');
    await page.getByRole('link', {name:'RO',exact:true}).click();
    await expect(page).toHaveURL(/\/ro\/register$/);
});

test('CSRF protection rejects a form submission without a session token', async ({request}) => {
    const result = await request.post('/ro/register', {form:{name:'Not created',email:'no-token@example.test',password:'not a valid request'}});
    expect(result.status()).toBe(419);
});
