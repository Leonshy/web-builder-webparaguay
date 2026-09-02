import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));

/**
 * Renderiza la galería de variantes y captura cada sección por separado.
 * Las secciones y sus anclas se leen del fixture, no se hardcodean.
 */
type Fixture = {
    pages: { slug: string; sections: { type: string; variant: string; anchor: string }[] }[];
};

const fixture: Fixture = JSON.parse(
    readFileSync(resolve(here, '../resources/schema/variants-gallery.json'), 'utf-8'),
);

const cases = fixture.pages.flatMap((page) =>
    page.sections.map((section) => ({
        slug: page.slug,
        anchor: section.anchor,
        name: `${section.type}__${section.variant}`,
    })),
);

test(`el fixture cubre las 41 variantes del catálogo`, () => {
    expect(cases.length).toBeGreaterThanOrEqual(41);
});

for (const c of cases) {
    test(`${c.name}`, async ({ page }) => {
        await page.goto(`/variants/${c.slug}`, { waitUntil: 'load' });
        await page.emulateMedia({ reducedMotion: 'reduce' });

        const section = page.locator(`[id="${c.anchor}"]`);
        await expect(section).toBeVisible();

        // El contenido tiene que estar en el DOM servido: sin texto vacío.
        await expect(section).not.toBeEmpty();

        await expect(section).toHaveScreenshot(`${c.name}.png`, {
            maxDiffPixelRatio: 0.02,
            animations: 'disabled',
        });
    });
}
