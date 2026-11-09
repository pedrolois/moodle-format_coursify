<?php
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
 * Switch courses from the Tiles format to Formatium, keeping their tile photos, icons and colour.
 *
 * Usage:
 *   php course/format/formatium/cli/import_tiles.php --courseid=12
 *   php course/format/formatium/cli/import_tiles.php --all
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => 0, 'all' => false, 'help' => false],
    ['c' => 'courseid', 'a' => 'all', 'h' => 'help']
);

if ($options['help'] || (!$options['courseid'] && !$options['all'])) {
    cli_writeln("Switch courses from the Tiles format to Formatium, keeping their tile photos, icons and colour.

Options:
-c, --courseid=ID   Course to switch
-a, --all           Every course that uses the Tiles format
-h, --help          This help");
    exit(0);
}

$conditions = ['format' => 'tiles'];
if ($options['courseid']) {
    $conditions['id'] = (int) $options['courseid'];
}
$courses = $DB->get_records('course', $conditions, 'id', 'id');
if (!$courses) {
    cli_writeln(get_string('importtiles_none', 'format_formatium'));
    exit(0);
}

\core\session\manager::set_user(get_admin());
foreach ($courses as $record) {
    // The options the course had with Tiles, before the switch.
    $old = (array) course_get_format($record->id)->get_course();
    // The switch imports the look through format_formatium::update_course_format_options().
    update_course((object) ['id' => $record->id, 'format' => 'formatium']);
    $counts = \format_formatium\local\tiles_importer::$lastcounts;
    $counts['course'] = format_string($old['fullname']);
    cli_writeln(get_string('importtiles_done', 'format_formatium', (object) $counts));
}
