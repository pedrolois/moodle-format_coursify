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
 * Before the run: create the temporary accounts and the test course (fixture.php), saved for the tests in
 * .auth/fixture.json (git-ignored). The first page after a cache purge can be slow: warm the site up once.
 *
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const {execFileSync} = require('child_process');
const fs = require('fs');
const path = require('path');
require('dotenv').config({path: path.resolve(__dirname, '.env')});

module.exports = async() => {
    const output = execFileSync(process.env.PHP_BINARY || 'php', [path.join(__dirname, 'fixture.php'), 'create'],
        {encoding: 'utf8'});
    const fixture = JSON.parse(output.trim().split('\n').pop());
    fs.mkdirSync(path.join(__dirname, '.auth'), {recursive: true});
    fs.writeFileSync(path.join(__dirname, '.auth', 'fixture.json'), JSON.stringify(fixture));

    const url = (process.env.MOODLE_URL || 'http://localhost/moodle').replace(/\/+$/, '');
    for (let attempt = 0; attempt < 3; attempt++) {
        try {
            await fetch(url + '/login/index.php', {signal: AbortSignal.timeout(300000)});
            break;
        } catch (e) {
            // The site may still be rebuilding its caches.
        }
    }
};
