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
 * What a teacher does: edit the course with the standard course editor and set the look of a tile.
 *
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const {test, expect} = require('@playwright/test');
const {fixture, setOptions, login, openCourse, tile} = require('./helpers');

test.describe('Teacher', () => {
    test.beforeEach(async({page}) => {
        setOptions({layout: 'grid', tilestyle: 'card', showbanner: 1});
        await login(page, 'teacher');
    });

    test('edit mode is the standard course editor', async({page}) => {
        await openCourse(page);
        await expect(page.locator('[data-region="coursify-tile"]')).toHaveCount(5);
        await Promise.all([page.waitForNavigation(), page.locator('input[name="setmode"]').first().click()]);
        await expect(page.locator('[data-region="coursify-tile"]')).toHaveCount(0);
        await expect(page.locator('li.section[data-number]')).toHaveCount(6);
        await expect(page.locator('[data-action="addSection"]').first()).toBeVisible();
        await Promise.all([page.waitForNavigation(), page.locator('input[name="setmode"]').first().click()]);
    });

    test('the section form sets the colour, icon and group of a tile', async({page}) => {
        await page.goto(`course/editsection.php?id=${fixture().sections[3]}`);
        await expect(page.locator('#id_coursifytile')).toBeVisible();
        await page.locator('#id_tilecolor').selectOption('purple');
        await page.locator('#id_tileicon').selectOption('flask');
        await page.locator('#id_groupname').fill('Lab work');
        await expect(page.locator('#fitem_id_tileimage_filemanager')).toBeVisible();
        await page.locator('#id_submitbutton').click();

        await openCourse(page);
        await expect(tile(page, 3)).toHaveClass(/coursify-tile--purple/);
        await expect(tile(page, 3).locator('.fa-flask')).toBeVisible();
        await expect(page.locator('.coursify-group__title')).toContainText(['Unit 1', 'Lab work', 'Unit 2']);

        // Back as it was.
        await page.goto(`course/editsection.php?id=${fixture().sections[3]}`);
        await page.locator('#id_tilecolor').selectOption('');
        await page.locator('#id_tileicon').selectOption('');
        await page.locator('#id_groupname').fill('');
        await page.locator('#id_submitbutton').click();
    });

    test('the course settings have the Coursify options', async({page}) => {
        await page.goto(`course/edit.php?id=${fixture().courseid}`);
        for (const name of ['layout', 'tilestyle', 'maptheme', 'tilepalette', 'numbering', 'showbanner', 'sectiondates',
            'activitymodal', 'courseindex']) {
            await expect(page.locator(`#id_${name}`)).toHaveCount(1);
        }
        await expect(page.locator('#id_layout option')).toHaveText(['Grid', 'Learning path', 'List', 'Map']);
    });

    test('teachers see no progress of their own', async({page}) => {
        await openCourse(page);
        await expect(page.locator('.coursify-summary__progress')).toHaveCount(0);
        await expect(page.locator('.coursify-ring')).toHaveCount(0);
    });
});
