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

namespace format_coursify\output;

/**
 * Tests of the course page of the Moodle app.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify\output\mobile::class)]
final class mobile_test extends \advanced_testcase {
    /**
     * The app gets the template and, for each section, its look and progress.
     */
    public function test_mobile_course_view(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 2,
            'enablecompletion' => 1]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $section = get_fast_modinfo($course)->get_section_info(1);
        course_get_format($course)->update_section_format_options(['id' => $section->id, 'tilecolor' => 'teal']);
        $this->setUser($student);

        $result = mobile::mobile_course_view(['courseid' => $course->id, 'userid' => $student->id]);
        $this->assertSame('main', $result['templates'][0]['id']);
        $this->assertStringContainsString('core-course-module', $result['templates'][0]['html']);
        $this->assertStringContainsString('{{ cf.progress }}', $result['templates'][0]['html']);

        $data = json_decode($result['otherdata']['coursify'], true);
        $this->assertSame('teal', $data['tiles'][$section->id]['color']);
        $this->assertSame(0, $data['tiles'][$section->id]['progress']);
        $this->assertSame(0, $data['progress']);
        $this->assertSame(1, $data['progresstotal']);
        $this->assertTrue($data['tiles'][$section->id]['next']);
        $this->assertStringContainsString('id=' . $page->cmid, $data['continueurl']);
    }

    /**
     * Users without access to the course get nothing.
     */
    public function test_no_access(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\require_login_exception::class);
        mobile::mobile_course_view(['courseid' => $course->id]);
    }
}
