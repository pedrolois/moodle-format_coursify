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

namespace format_coursify\local;

use completion_info;

/**
 * Tests of the data of the tiles: look, progress, locks, dates, groups, Continue and Resume.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify\local\tiles::class)]
final class tiles_test extends \advanced_testcase {
    /**
     * A course with four sections of two pages each, with manual completion, and an enrolled student.
     *
     * @param array $options course options
     * @return array [course, student, cms by section number]
     */
    protected function create_course(array $options = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array_merge(['format' => 'coursify', 'numsections' => 4,
            'enablecompletion' => 1], $options));
        $cms = [];
        for ($section = 1; $section <= 4; $section++) {
            for ($i = 1; $i <= 2; $i++) {
                $cms[$section][] = $generator->create_module('page', ['course' => $course->id, 'section' => $section,
                    'completion' => COMPLETION_TRACKING_MANUAL]);
            }
        }
        $student = $generator->create_and_enrol($course, 'student');
        return [$course, $student, $cms];
    }

    /**
     * Mark an activity complete for a user.
     *
     * @param \stdClass $course
     * @param \stdClass $module
     * @param \stdClass $user
     */
    protected function complete(\stdClass $course, \stdClass $module, \stdClass $user): void {
        $cm = get_fast_modinfo($course)->get_cm($module->cmid);
        (new completion_info($course))->update_state($cm, COMPLETION_COMPLETE, $user->id);
    }

    /**
     * Numbers in each numbering style.
     *
     * @param int $number
     * @param string $style
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('numbering_provider')]
    public function test_format_number(int $number, string $style, string $expected): void {
        $this->assertSame($expected, tiles::format_number($number, $style));
    }

    /**
     * Numbers to format.
     *
     * @return array
     */
    public static function numbering_provider(): array {
        return [
            [1, tiles::NUMBERING_NUMBER, '1'],
            [12, tiles::NUMBERING_NUMBER, '12'],
            [1, tiles::NUMBERING_LETTER, 'A'],
            [26, tiles::NUMBERING_LETTER, 'Z'],
            [27, tiles::NUMBERING_LETTER, 'AA'],
            [4, tiles::NUMBERING_ROMAN, 'IV'],
            [9, tiles::NUMBERING_ROMAN, 'IX'],
            [14, tiles::NUMBERING_ROMAN, 'XIV'],
            [1994, tiles::NUMBERING_ROMAN, 'MCMXCIV'],
        ];
    }

    /**
     * Groups start at the sections with a group name.
     */
    public function test_group_tiles(): void {
        $tile = fn($id, $group, $complete = false) => ['id' => $id, 'groupname' => $group,
            'progress' => ['complete' => $complete]];
        $groups = tiles::group_tiles([$tile(1, ''), $tile(2, 'Unit 1', true), $tile(3, ''), $tile(4, 'Unit 2')]);
        $this->assertCount(3, $groups);
        $this->assertFalse($groups[0]['hasname']);
        $this->assertSame('Unit 1', $groups[1]['name']);
        $this->assertSame([2, 3], array_column($groups[1]['tiles'], 'id'));
        $this->assertSame(1, $groups[1]['done']);
        $this->assertSame(2, $groups[1]['count']);
        $this->assertSame('Unit 2', $groups[2]['name']);
        $this->assertSame([], tiles::group_tiles([]));
    }

    /**
     * Each tile has its look, number, progress and lock.
     */
    public function test_export_tiles(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student, $cms] = $this->create_course(['tilepalette' => 'ocean', 'numbering' => 'roman']);
        $format = course_get_format($course);
        $modinfo = get_fast_modinfo($course);
        $format->update_section_format_options(['id' => $modinfo->get_section_info(1)->id, 'tilecolor' => 'red',
            'tileicon' => 'rocket', 'groupname' => 'Unit 1']);
        // Section 3 is locked until the first activity of section 1 is complete.
        $availability = json_encode(['op' => '&', 'c' => [['type' => 'completion', 'cm' => (int) $cms[1][0]->cmid,
            'e' => COMPLETION_COMPLETE]], 'showc' => [true]]);
        $DB->set_field('course_sections', 'availability', $availability, ['id' => $modinfo->get_section_info(3)->id]);
        rebuild_course_cache($course->id, true);
        $this->complete($course, $cms[2][0], $student);

        $this->setUser($student);
        $tiles = (new tiles(course_get_format($course->id)))->export_tiles();
        $this->assertCount(4, $tiles);

        // Own colour and icon; the palette colours the others in turn.
        $this->assertSame('red', $tiles[0]['color']);
        $this->assertSame('rocket', $tiles[0]['icon']);
        $this->assertSame('Unit 1', $tiles[0]['groupname']);
        $this->assertSame('cyan', $tiles[1]['color']);
        $this->assertSame('teal', $tiles[2]['color']);
        $this->assertSame(['I', 'II', 'III', 'IV'], array_column($tiles, 'steplabel'));
        $this->assertSame('IV', $tiles[0]['steps']);

        // Progress.
        $this->assertSame(0, $tiles[0]['progress']['done']);
        $this->assertSame(get_string('progress_start', 'format_coursify'), $tiles[0]['progress']['label']);
        $this->assertSame(1, $tiles[1]['progress']['done']);
        $this->assertSame(50, $tiles[1]['progress']['percent']);
        $this->assertFalse($tiles[1]['progress']['complete']);

        // Locked, with the reason and without link or progress.
        $this->assertTrue($tiles[2]['locked']);
        $this->assertSame('', $tiles[2]['url']);
        $this->assertNotEmpty($tiles[2]['lockinfo']);
        $this->assertArrayNotHasKey('progress', $tiles[2]);
        $this->assertFalse($tiles[3]['locked']);
        $this->assertStringContainsString('/course/section.php', $tiles[3]['url']);
    }

    /**
     * A section complete shows as done; the course progress counts every visible activity.
     */
    public function test_progress(): void {
        $this->resetAfterTest();
        [$course, $student, $cms] = $this->create_course();
        $this->complete($course, $cms[1][0], $student);
        $this->complete($course, $cms[1][1], $student);
        $this->complete($course, $cms[2][0], $student);

        $this->setUser($student);
        $tiles = new tiles(course_get_format($course->id));
        $exported = $tiles->export_tiles();
        $this->assertTrue($exported[0]['progress']['complete']);
        $this->assertSame(get_string('progress_done', 'format_coursify'), $exported[0]['progress']['label']);
        $overall = $tiles->export_overall_progress();
        $this->assertSame(3, $overall['done']);
        $this->assertSame(8, $overall['total']);
        $this->assertSame(37, $overall['percent']);
    }

    /**
     * Progress is only for the users whose completion is tracked.
     */
    public function test_progress_only_for_tracked_users(): void {
        $this->resetAfterTest();
        [$course] = $this->create_course();
        $this->setAdminUser();
        $tiles = new tiles(course_get_format($course->id));
        $this->assertArrayNotHasKey('progress', $tiles->export_tiles()[0]);
        $this->assertNull($tiles->export_overall_progress());
        $this->assertNull($tiles->export_continue());
    }

    /**
     * Continue goes to the first activity not completed yet, in course order.
     */
    public function test_export_continue(): void {
        $this->resetAfterTest();
        [$course, $student, $cms] = $this->create_course();
        $this->complete($course, $cms[1][0], $student);
        $this->setUser($student);

        $next = (new tiles(course_get_format($course->id)))->export_continue();
        $this->assertSame((int) $cms[1][1]->cmid, (int) (new \moodle_url($next['url']))->get_param('id'));
        $this->assertSame((int) get_fast_modinfo($course)->get_section_info(1)->id, (int) $next['sectionid']);

        foreach ($cms as $sectioncms) {
            foreach ($sectioncms as $module) {
                $this->complete($course, $module, $student);
            }
        }
        $this->assertNull((new tiles(course_get_format($course->id)))->export_continue());
    }

    /**
     * Weekly dates: each section follows the previous one; "This week" is the current section; future ones show when
     * they open; sections without time get no dates.
     */
    public function test_section_dates(): void {
        $this->resetAfterTest();
        $start = usergetmidnight(time()) - 10 * DAYSECS;
        [$course, $student] = $this->create_course(['sectiondates' => tiles::DATES_WEEKLY, 'startdate' => $start]);
        $format = course_get_format($course);
        $format->update_section_format_options(['id' => get_fast_modinfo($course)->get_section_info(4)->id, 'duration' => 0]);
        $this->setUser($student);

        $tiles = (new tiles(course_get_format($course->id)))->export_tiles();
        $this->assertFalse($tiles[0]['isthisweek']);
        $this->assertTrue($tiles[1]['isthisweek']);
        $this->assertTrue($tiles[1]['iscurrent']);
        $this->assertArrayHasKey('opens', $tiles[2]);
        $this->assertArrayNotHasKey('daterange', $tiles[3]);
        $this->assertArrayHasKey('daterange', $tiles[0]);
    }

    /**
     * Resume: the last section page visited, while the user can still see it.
     */
    public function test_last_section(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->create_course();
        $this->setUser($student);
        $tiles = new tiles(course_get_format($course->id));
        $this->assertNull($tiles->export_last_section());

        $section = get_fast_modinfo($course)->get_section_info(2);
        $tiles->remember_section($section->id);
        $this->assertEquals($section->id, get_user_preferences(tiles::PREF_LASTSECTION . $course->id));
        $last = $tiles->export_last_section();
        $this->assertEquals($section->id, $last['sectionid']);

        // Hidden from students: no longer offered.
        \core_courseformat\formatactions::section($course->id)->set_visibility($section, false);
        $this->assertNull((new tiles(course_get_format($course->id)))->export_last_section());
    }

    /**
     * The banner falls back to a generated image and lists the course contacts.
     */
    public function test_export_banner(): void {
        global $PAGE;
        $this->resetAfterTest();
        [$course, $student] = $this->create_course(['summary' => '<p>About this course.</p>']);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher', ['firstname' => 'Ana']);
        $this->setUser($student);

        $banner = (new tiles(course_get_format($course->id)))->export_banner($PAGE->get_renderer('core'));
        $this->assertFalse($banner['hasimage']);
        $this->assertStringStartsWith('data:image/', $banner['imageurl']);
        $this->assertSame('About this course.', $banner['summary']);
        $this->assertSame(50, $banner['position']);
        $this->assertTrue($banner['hascontacts']);
        $this->assertStringContainsString('Ana', $banner['contacts'][0]['name']);
        $this->assertStringContainsString('id=' . $teacher->id, $banner['contacts'][0]['url']);
    }
}
