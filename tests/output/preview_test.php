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
 * Tests of the live preview of the course edit form.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('format_coursify_output_fragment_preview')]
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify::class)]
final class preview_test extends \advanced_testcase {
    /**
     * Course with a few sections and the teacher who edits it.
     *
     * @return array course and teacher
     */
    protected function setup_course(): array {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/coursify/lib.php');
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'coursify', 'numsections' => 3, 'layout' => 'grid']);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        return [$course, $teacher];
    }

    /**
     * Preview of the course with some options.
     *
     * @param \stdClass $course
     * @param array $options
     * @return string HTML
     */
    protected function preview(\stdClass $course, array $options): string {
        global $PAGE;
        $context = \context_course::instance($course->id);
        $PAGE->set_context($context);
        $PAGE->set_url('/course/edit.php', ['id' => $course->id]);
        return format_coursify_output_fragment_preview(['context' => $context, 'options' => json_encode($options)]);
    }

    /**
     * The preview uses the chosen layout and map theme without saving them.
     */
    public function test_preview_uses_the_chosen_options(): void {
        [$course, $teacher] = $this->setup_course();
        $this->setUser($teacher);

        $this->assertStringContainsString('coursify-grid', $this->preview($course, []));
        $html = $this->preview($course, ['layout' => 'map', 'maptheme' => 'space']);
        $this->assertStringContainsString('coursify-map--space', $html);
        $this->assertStringNotContainsString('coursify-grid', $html);
        $this->assertStringContainsString('coursify-list', $this->preview($course, ['layout' => 'list']));

        // Nothing is saved.
        $this->assertEquals('grid', course_get_format($course->id)->get_format_options()['layout']);
    }

    /**
     * Values that are not a choice of their setting are ignored.
     */
    public function test_invalid_values_are_ignored(): void {
        [$course, $teacher] = $this->setup_course();
        $this->setUser($teacher);
        $html = $this->preview($course, ['layout' => '<script>', 'maptheme' => 'nope', 'unknown' => 'x']);
        $this->assertStringContainsString('coursify-grid', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * Teachers in edit mode also see the course as learners do.
     */
    public function test_preview_is_never_the_editor(): void {
        global $USER;
        [$course, $teacher] = $this->setup_course();
        $this->setUser($teacher);
        $USER->editing = 1;
        $html = $this->preview($course, ['layout' => 'path']);
        $this->assertStringContainsString('coursify-path', $html);
    }

    /**
     * Only users who can edit the course can preview it.
     */
    public function test_preview_needs_course_update(): void {
        [$course] = $this->setup_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        $this->preview($course, []);
    }
}
