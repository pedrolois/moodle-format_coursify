// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * What a student sees: tiles, progress, Continue, locks, groups, filters, layouts, section pages and the activity window.
 *
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const {test, expect} = require('@playwright/test');
const {fixture, php, setOptions, login, openCourse, tile} = require('./helpers');

test.describe('Student', () => {
    test.beforeEach(async({page}) => {
        php('reset');
        setOptions({layout: 'grid', tilestyle: 'card', showbanner: 1, showprogress: 1, showcontinue: 1, showfilters: 1,
            activitymodal: 1, sectiondates: 'none', numbering: 'number', tilepalette: ''});
        await login(page, 'student');
    });

    test('the course page shows a tile per section with its progress', async({page}) => {
        await openCourse(page);
        await expect(page.locator('[data-region="coursify-tile"]')).toHaveCount(5);
        await expect(tile(page, 1)).toHaveClass(/coursify-tile--complete/);
        await expect(tile(page, 1)).toHaveClass(/coursify-tile--green/);
        await expect(tile(page, 1).locator('.fa-rocket')).toBeVisible();
        await expect(tile(page, 2).locator('.coursify-tile__number')).toHaveText('2');
        await expect(tile(page, 1)).toContainText('Done');
        await expect(tile(page, 2)).toContainText('Start');
        // 2 of the 9 activities the student can see (the locked section does not count).
        await expect(page.locator('.coursify-summary__label strong')).toHaveText('22%');
    });

    test('Continue goes to the next activity to do, and its section is "Up next"', async({page}) => {
        await openCourse(page);
        await expect(page.locator('.coursify-continue')).toContainText('Basics page 1');
        await expect(tile(page, 2)).toHaveClass(/coursify-tile--next/);
        await expect(tile(page, 2).locator('.coursify-badge-next')).toBeVisible();
        await page.locator('.coursify-continue').click();
        await expect(page).toHaveURL(/mod\/page\/view\.php/);
    });

    test('a locked section tells why and has no link', async({page}) => {
        await openCourse(page);
        await expect(tile(page, 4)).toHaveClass(/coursify-tile--locked/);
        await expect(tile(page, 4)).toContainText('Not available unless');
        await expect(tile(page, 4).locator('a.coursify-tile__link')).toHaveCount(0);
        await expect(tile(page, 4)).toHaveAttribute('data-status', 'locked');
    });

    test('groups have a heading with their progress', async({page}) => {
        await openCourse(page);
        const groups = page.locator('.coursify-group__title');
        await expect(groups).toHaveText(['Unit 1', 'Unit 2']);
        await expect(page.locator('.coursify-group').first()).toContainText('1 of 3 sections done');
    });

    test('filters show only the sections to do or done', async({page}) => {
        await openCourse(page);
        await page.locator('[data-filter="done"]').click();
        await expect(page.locator('[data-region="coursify-tile"]:visible')).toHaveCount(1);
        await expect(page.locator('[data-filter="done"]')).toHaveAttribute('aria-pressed', 'true');
        await page.locator('[data-filter="todo"]').click();
        await expect(page.locator('[data-region="coursify-tile"]:visible')).toHaveCount(3);
        await page.locator('[data-filter="all"]').click();
        await expect(page.locator('[data-region="coursify-tile"]:visible')).toHaveCount(5);
    });

    test('the banner shows the course summary and contacts', async({page}) => {
        await openCourse(page);
        await expect(page.locator('.coursify-banner')).toContainText('A course to test Coursify.');
        await expect(page.locator('.coursify-banner__contact')).toContainText('Teacher Coursify');
        setOptions({showbanner: 0});
        await openCourse(page);
        await expect(page.locator('.coursify-banner')).toHaveCount(0);
        await expect(page.locator('.coursify-summary')).toBeVisible();
    });

    test('a section page has the section strip and previous/next', async({page}) => {
        await openCourse(page);
        await tile(page, 2).locator('a').click();
        await expect(page).toHaveURL(new RegExp(`section\\.php\\?id=${fixture().sections[2]}`));
        await expect(page.locator('.coursify-strip [aria-current="page"]')).toContainText('Basics');
        await expect(page.locator('.coursify-sectionnav')).toContainText('Welcome');
        await expect(page.locator('.coursify-sectionnav')).toContainText('Practice');
        await page.locator('.coursify-sectionnav').getByText('All sections').click();
        await expect(page.locator('[data-region="coursify-tile"]')).toHaveCount(5);
    });

    test('a page opens in a window and closing it shows its completion', async({page}) => {
        await page.goto(`course/section.php?id=${fixture().sections[2]}`);
        const video = page.locator('[data-for="cmitem"]', {hasText: 'Basics video'});
        await expect(video).toContainText('To do');
        await video.locator('a.aalink').click();
        const modal = page.locator('.modal.show');
        await expect(modal.locator('.coursify-modal__video iframe')).toHaveAttribute('src',
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
        await modal.locator('.btn-close').click();
        await expect(page.locator('[data-for="cmitem"]', {hasText: 'Basics video'})).toContainText('Done');

        await page.locator('[data-for="cmitem"] a.aalink', {hasText: 'Basics page 1'}).click();
        await expect(page.locator('.modal.show .coursify-modal__html')).toContainText('Content of Basics page 1.');
        await expect(page).toHaveURL(/section\.php/);
    });

    test('the activity window can be turned off', async({page}) => {
        setOptions({activitymodal: 0});
        await page.goto(`course/section.php?id=${fixture().sections[2]}`);
        await page.locator('[data-for="cmitem"] a.aalink', {hasText: 'Basics page 1'}).click();
        await expect(page).toHaveURL(/mod\/page\/view\.php/);
    });

    for (const [layout, item] of [['path', '.coursify-step'], ['list', '.coursify-row'], ['map', '.coursify-map__stop']]) {
        test(`the ${layout} layout shows every section`, async({page}) => {
            setOptions({layout});
            await openCourse(page);
            await expect(page.locator(item)).toHaveCount(5);
            await expect(page.locator(`${item}.coursify-tile--next`)).toHaveCount(1);
        });
    }

    test('the map draws its trail and reflows on narrow screens', async({page}) => {
        setOptions({layout: 'map', maptheme: 'treasure'});
        await openCourse(page);
        const trail = page.locator('.coursify-map__base');
        await expect(trail).toHaveAttribute('d', /^M /);
        await expect(page.locator('.coursify-map__avatar')).toHaveCount(1);
        const wide = await page.locator('.coursify-map').evaluate(map => getComputedStyle(map)
            .getPropertyValue('--coursify-map-columns').trim());
        await page.setViewportSize({width: 400, height: 900});
        await expect.poll(() => page.locator('.coursify-map').evaluate(map => getComputedStyle(map)
            .getPropertyValue('--coursify-map-columns').trim())).not.toBe(wide);
    });

    test('weekly dates show this week and when future sections open', async({page}) => {
        setOptions({sectiondates: 'weekly'});
        await openCourse(page);
        await expect(tile(page, 2)).toContainText('This week');
        await expect(tile(page, 3)).toContainText('Opens');
        await expect(tile(page, 1).locator('.coursify-tile__dates')).toBeVisible();
    });

    test('Roman numbering and the photo style', async({page}) => {
        setOptions({numbering: 'roman', tilestyle: 'photo'});
        await openCourse(page);
        await expect(tile(page, 3).locator('.coursify-tile__number')).toHaveText('III');
        await expect(page.locator('.coursify-grid--photo').first()).toBeVisible();
    });

    test('the course index drawer does not cover the tiles', async({page}) => {
        await openCourse(page);
        const drawer = page.locator('#theme_boost-drawers-courseindex');
        if (!await drawer.evaluate(element => element.classList.contains('show'))) {
            await page.locator('.drawer-left-toggle button').click();
        }
        await expect(drawer).toHaveClass(/show/);
        await page.waitForTimeout(400);
        const right = (await drawer.boundingBox()).x + (await drawer.boundingBox()).width;
        const tiles = await page.locator('[data-region="coursify-tiles"]').boundingBox();
        expect(right).toBeLessThanOrEqual(tiles.x + 1);
    });
});
