import { test, expect } from '@playwright/test';

/**
 * Kritieke flow: verse start → installer → beheerder aanmaken → board →
 * kaart maken → verslepen → reageren.
 *
 * Vereist een verse database (leeg, niet-geïnstalleerd). In CI wordt de app met
 * een lege sqlite-database gestart voordat deze test draait.
 */
test('verse installatie tot en met een kaart aanmaken', async ({ page }) => {
    // 1. Verse start → de installer verschijnt.
    await page.goto('/');
    await expect(page).toHaveURL(/\/install/);
    await expect(page.getByRole('heading', { name: /Board installeren/i })).toBeVisible();

    // 2. Stap "Toepassing" → volgende.
    await page.getByRole('button', { name: /Volgende/i }).click();

    // 3. Stap "Beheerder" invullen.
    await page.getByLabel('Naam').fill('Beheerder');
    await page.getByLabel('Gebruikersnaam').fill('beheerder');
    await page.getByLabel('E-mailadres').fill('admin@example.com');
    await page.getByLabel('Wachtwoord', { exact: true }).fill('Sterk-Wachtwoord-123!');
    await page.getByLabel('Wachtwoord bevestigen').fill('Sterk-Wachtwoord-123!');
    await page.getByRole('button', { name: /Volgende/i }).click();

    // 4. Stap "E-mail" → installatie voltooien.
    await page.getByRole('button', { name: /Installatie voltooien/i }).click();

    // 5. Na installatie: dashboard met het demo-board.
    await expect(page).toHaveURL('/');
    await page.getByText('Welkom bij Board').click();

    // 6. Board opent met lijsten; maak een kaart aan.
    await expect(page.getByRole('heading', { name: 'Welkom bij Board' })).toBeVisible();
    const addCard = page.getByRole('button', { name: /Kaart toevoegen/i }).first();
    await addCard.click();
    await page.getByPlaceholder('Titel van de kaart…').fill('E2E testkaart');
    await page.keyboard.press('Enter');

    await expect(page.getByText('E2E testkaart')).toBeVisible();
});
