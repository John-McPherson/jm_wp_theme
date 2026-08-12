import { expect, test } from './fixtures/test';

test.describe('site header', () => {
	test('renders the block-based header', async ({ page }) => {
		await page.goto('/');

		await expect(page.locator('.jmc-header')).toBeVisible();
		await expect(page.locator('.jmc-header-inner')).toBeVisible();
		await expect(page.locator('.jmc-logo')).toBeVisible();
	});

	test('uses sticky positioning', async ({ page }) => {
		await page.goto('/');

		const header = page.locator('.jmc-header');

		await expect(header).toHaveCSS('position', 'sticky');
		await expect(header).toHaveCSS('top', '0px');
	});

	test('remains at the viewport edge while scrolling', async ({ page }) => {
		await page.goto('/');

		const header = page.locator('.jmc-header');
		await expect(header).toBeVisible();

		await page.evaluate(() => {
			const spacer = document.createElement('div');
			spacer.dataset.testSpacer = 'true';
			spacer.style.height = '200vh';
			document.body.append(spacer);
			window.scrollTo(0, 600);
		});

		await expect
			.poll(async () => {
				const box = await header.boundingBox();
				return Math.round(box?.y ?? -1);
			})
			.toBe(0);
	});

	test('disables sticky positioning in a short viewport', async ({ page }) => {
		await page.setViewportSize({ width: 1024, height: 470 });
		await page.goto('/');

		await expect(page.locator('.jmc-header')).toHaveCSS('position', 'static');
	});

	test('does not create horizontal overflow at 320px', async ({ page }) => {
		await page.setViewportSize({ width: 320, height: 800 });
		await page.goto('/');

		const dimensions = await page.evaluate(() => ({
			clientWidth: document.documentElement.clientWidth,
			scrollWidth: document.documentElement.scrollWidth,
		}));

		expect(dimensions.scrollWidth).toBeLessThanOrEqual(
			dimensions.clientWidth
		);
	});
});

test('administrator can open the Site Editor', async ({ admin, page }) => {
	await admin.visitAdminPage('site-editor.php');

	await expect(page).toHaveURL(/site-editor\.php/);
	await expect(
		page.getByText(/this block contains unexpected or invalid content/i)
	).toHaveCount(0);
	await expect(
		page.getByRole('button', { name: /attempt block recovery/i })
	).toHaveCount(0);
});
