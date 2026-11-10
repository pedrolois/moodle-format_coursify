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
 * Helpers of the Coursify end-to-end tests.
 *
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const {expect} = require('@playwright/test');
const {execFileSync} = require('child_process');
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');

/**
 * Data of the run: accounts, course and section ids.
 *
 * @returns {object}
 */
const fixture = () => JSON.parse(fs.readFileSync(path.join(root, '.auth', 'fixture.json'), 'utf8'));

/**
 * Run a command of fixture.php.
 *
 * @param {...string} args
 */
const php = (...args) => {
    execFileSync(process.env.PHP_BINARY || 'php', [path.join(root, 'fixture.php'), ...args], {encoding: 'utf8'});
};

/**
 * Set course format options of the test course.
 *
 * @param {object} options
 */
const setOptions = options => php('options', JSON.stringify(options));

/**
 * Log in with the standard form (the site may show only single sign-on without showcore).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} role teacher or student
 */
const login = async(page, role) => {
    const user = fixture().users[role];
    await page.goto('login/index.php?showcore=1');
    await page.locator('#username').fill(user.username);
    await page.locator('#password').fill(user.password);
    await page.locator('#loginbtn').click();
    await expect(page.locator('#loginbtn')).toHaveCount(0);
};

/**
 * Open the test course.
 *
 * @param {import('@playwright/test').Page} page
 */
const openCourse = async page => {
    await page.goto(`course/view.php?id=${fixture().courseid}`);
    await page.waitForLoadState('networkidle');
};

/**
 * Tile of a section, by section number.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} number
 * @returns {import('@playwright/test').Locator}
 */
const tile = (page, number) => page.locator(`[data-region="coursify-tile"][data-number="${number}"]`);

module.exports = {fixture, php, setOptions, login, openCourse, tile};
