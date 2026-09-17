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


    const username = 'qa_' + id.replaceAll('-', '').slice(0, 24);
    await page.goto('/ro/account/profile');
    await page.getByLabel('Nume de utilizator', {exact:true}).fill(username);
    await page.getByLabel('Despre tine', {exact:true}).fill('Îmi plac lucrurile îngrijite și poveștile lor.');
    await page.getByLabel('Localitate', {exact:true}).fill('Cluj-Napoca');
    await page.getByLabel('Telefon', {exact:true}).fill('0722 123 456');
    await page.getByLabel('Fotografie sau logo', {exact:true}).setInputFiles('tests/fixtures/avatar.png');
    await page.getByRole('button', {name:'Salvează profilul',exact:true}).click();
    await expect(page.getByRole('status')).toContainText('Profilul a fost salvat.');
    await expect(page.locator('.profile-sidebar img')).toHaveJSProperty('naturalWidth',320);
    const avatar = await page.locator('.profile-sidebar img').getAttribute('src');
    const guest = await browser.newContext({baseURL});
    const publicPage = await guest.newPage();
    expect((await guest.request.get(new URL(avatar).pathname)).status()).toBe(404);
    expect((await publicPage.goto('/ro/sellers/' + username)).status()).toBe(404);
    await page.getByLabel('Afișează profilul meu public',{exact:true}).check();
    await page.getByLabel('Tipul contului',{exact:true}).selectOption('business');
    await page.getByLabel('Numele firmei',{exact:true}).fill('Atelierul Aproape QA');
    await page.getByLabel('CUI / CIF',{exact:true}).fill('RO12345678');
    await page.getByRole('button', {name:'Salvează profilul',exact:true}).click();
    await expect(page.getByRole('status')).toContainText('Profilul a fost salvat.');
    expect((await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze()).violations).toEqual([]);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({path:testInfo.outputPath('profile-edit.png'),fullPage:true});
    expect((await publicPage.goto('/ro/sellers/' + username)).status()).toBe(200);
    await expect(publicPage.getByRole('heading',{level:1})).toHaveText('Atelierul Aproape QA');
    await expect(publicPage.locator('.profile-avatar')).toHaveJSProperty('naturalWidth',320);
    const publicHTML = await publicPage.content();
    expect(publicHTML).not.toContain(email);
    expect(publicHTML).not.toContain('40722123456');
    expect(publicHTML).not.toContain('RO12345678');
    expect((await new AxeBuilder({page:publicPage}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze()).violations).toEqual([]);
    await publicPage.screenshot({path:testInfo.outputPath('public-profile.png'),fullPage:true});
    await page.getByLabel('Afișează profilul meu public',{exact:true}).uncheck();
    await page.getByRole('button', {name:'Salvează profilul',exact:true}).click();
    await expect(page.getByRole('status')).toContainText('Profilul a fost salvat.');
    expect((await publicPage.goto('/ro/sellers/' + username)).status()).toBe(404);
    await guest.close();
    await page.goto('/ro/account');

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
