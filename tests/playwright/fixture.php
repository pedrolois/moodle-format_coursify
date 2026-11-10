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
 * Test data of the Coursify browser tests: created before a run, removed after it.
 *
 * Usage: php fixture.php create                 prints a JSON object {users: {role: {username, password}}, courseid, ...}
 *        php fixture.php options '{"layout": "path"}'   sets course format options of the test course
 *        php fixture.php reset                  clears the completion of the student
 *        php fixture.php delete                 removes the accounts and the course
 *
 * The accounts get a random password, so no real person's account and no stored password are needed.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

/** Prefix of the usernames and of the course short name. */
const COURSIFY_E2E = 'coursify_e2e_';

$action = $argv[1] ?? '';
\core\session\manager::set_user(get_admin());

/**
 * The test course, if it exists.
 *
 * @return stdClass|false
 */
function coursify_e2e_course() {
    global $DB;
    return $DB->get_record('course', ['shortname' => COURSIFY_E2E . 'course']);
}

/**
 * Remove the accounts and the course.
 */
function coursify_e2e_delete(): void {
    global $DB;
    if ($course = coursify_e2e_course()) {
        delete_course($course, false);
    }
    foreach (['teacher', 'student'] as $role) {
        if ($user = $DB->get_record('user', ['username' => COURSIFY_E2E . $role, 'deleted' => 0])) {
            delete_user($user);
        }
    }
}

if ($action === 'delete') {
    coursify_e2e_delete();
    echo "{}\n";
    exit(0);
}

if ($action === 'options') {
    $course = coursify_e2e_course();
    course_get_format($course)->update_course_format_options(json_decode($argv[2], true));
    rebuild_course_cache($course->id, true);
    echo "{}\n";
    exit(0);
}

if ($action === 'reset') {
    global $DB;
    $course = coursify_e2e_course();
    $student = $DB->get_record('user', ['username' => COURSIFY_E2E . 'student']);
    $cmids = array_keys(get_fast_modinfo($course)->get_cms());
    [$insql, $params] = $DB->get_in_or_equal($cmids);
    $DB->delete_records_select(
        'course_modules_completion',
        "userid = ? AND coursemoduleid $insql",
        array_merge([$student->id], $params)
    );
    $DB->delete_records_select(
        'course_modules_viewed',
        "userid = ? AND coursemoduleid $insql",
        array_merge([$student->id], $params)
    );
    // Section 1 complete, as at the start of the run.
    $completion = new completion_info($course);
    foreach (get_fast_modinfo($course)->get_section_info(1)->get_sequence_cm_infos() as $cm) {
        $completion->update_state($cm, COMPLETION_COMPLETE, $student->id);
    }
    cache::make('core', 'completion')->purge();
    echo "{}\n";
    exit(0);
}

if ($action !== 'create') {
    fwrite(STDERR, "Usage: php fixture.php create|options|reset|delete\n");
    exit(1);
}

coursify_e2e_delete();
$generator = new testing_data_generator();
$users = [];
foreach (['teacher', 'student'] as $role) {
    $password = 'Cf-' . random_string(12) . '9!';
    $user = $generator->create_user(['username' => COURSIFY_E2E . $role, 'password' => $password,
        'firstname' => ucfirst($role), 'lastname' => 'Coursify']);
    $users[$role] = ['username' => $user->username, 'password' => $password, 'id' => $user->id];
}

$course = $generator->create_course(['fullname' => 'Coursify e2e', 'shortname' => COURSIFY_E2E . 'course',
    'format' => 'coursify', 'numsections' => 5, 'enablecompletion' => 1, 'summary' => 'A course to test Coursify.',
    'startdate' => usergetmidnight(time()) - 10 * DAYSECS]);
$generator->enrol_user($users['teacher']['id'], $course->id, 'editingteacher');
$generator->enrol_user($users['student']['id'], $course->id, 'student');

$names = [1 => 'Welcome', 2 => 'Basics', 3 => 'Practice', 4 => 'Project', 5 => 'Extras'];
$format = course_get_format($course);
$cms = [];
foreach ($names as $number => $name) {
    $section = get_fast_modinfo($course)->get_section_info($number);
    course_update_section($course, $section, ['name' => $name, 'summary' => "About $name.",
        'summaryformat' => FORMAT_HTML]);
    for ($i = 1; $i <= 2; $i++) {
        $cms[$number][] = $generator->create_module('page', ['course' => $course->id, 'section' => $number,
            'name' => "$name page $i", 'content' => "<p>Content of $name page $i.</p>",
            'completion' => COMPLETION_TRACKING_MANUAL]);
    }
}
$generator->create_module('url', ['course' => $course->id, 'section' => 2, 'name' => 'Basics video',
    'externalurl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'completion' => COMPLETION_TRACKING_AUTOMATIC,
    'completionview' => 1]);

$modinfo = get_fast_modinfo($course);
$format->update_section_format_options(['id' => $modinfo->get_section_info(1)->id, 'groupname' => 'Unit 1',
    'tilecolor' => 'green', 'tileicon' => 'rocket']);
$format->update_section_format_options(['id' => $modinfo->get_section_info(4)->id, 'groupname' => 'Unit 2']);
// Section 4 opens once the second page of section 3 is complete.
$availability = json_encode(['op' => '&', 'c' => [['type' => 'completion', 'cm' => (int) $cms[3][1]->cmid,
    'e' => COMPLETION_COMPLETE]], 'showc' => [true]]);
$DB->set_field('course_sections', 'availability', $availability, ['id' => $modinfo->get_section_info(4)->id]);
rebuild_course_cache($course->id, true);

// Section 1 complete for the student.
$completion = new completion_info($course);
foreach ($cms[1] as $module) {
    $completion->update_state(get_fast_modinfo($course)->get_cm($module->cmid), COMPLETION_COMPLETE, $users['student']['id']);
}

$sections = [];
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    $sections[$section->section] = (int) $section->id;
}
echo json_encode(['users' => $users, 'courseid' => (int) $course->id, 'sections' => $sections]) . "\n";
