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

namespace format_coursify;

use format_coursify\local\tiles;

/**
 * Tests of the course format class.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify::class)]
final class format_coursify_test extends \advanced_testcase {
    /**
     * Names of the sections.
     */
    public function test_section_names(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 2]);
        $format = course_get_format($course);
        $this->assertSame(get_string('section0name', 'format_coursify'), $format->get_section_name(0));
        $this->assertSame(get_string('newsection', 'format_coursify'), $format->get_section_name(1));
        course_update_section($course, get_fast_modinfo($course)->get_section_info(2), ['name' => 'Unit <b>2</b>']);
        $this->assertSame('Unit 2', strip_tags(course_get_format($course->id)->get_section_name(2)));
    }

    /**
     * Sections open on their own page.
     */
    public function test_get_view_url(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 2]);
        $format = course_get_format($course);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $url = $format->get_view_url($section, ['navigation' => true]);
        $this->assertStringEndsWith('/course/section.php', $url->get_path(false));
        $this->assertEquals($section->id, $url->get_param('id'));
        $this->assertStringEndsWith('/course/view.php', $format->get_view_url(null)->get_path(false));
    }

    /**
     * Default options of a new course, and the course index option.
     */
    public function test_course_options(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $format = course_get_format($course);
        $options = $format->get_format_options();
        $this->assertSame(tiles::LAYOUT_GRID, $options['layout']);
        $this->assertEquals(1, $options['showprogress']);
        $this->assertEquals(1, $options['activitymodal']);
        $this->assertTrue($format->uses_course_index());

        $format->update_course_format_options(['courseindex' => 0]);
        $this->assertFalse(course_get_format($course->id)->uses_course_index());
    }

    /**
     * The edit forms have every option, the section form has the tile image too.
     */
    public function test_form_options(): void {
        $format = course_get_format((object) ['id' => SITEID, 'format' => 'coursify']);
        foreach (
            ['layout', 'tilestyle', 'maptheme', 'tilepalette', 'tilecolumns', 'numbering', 'showbanner',
                'bannerposition', 'showprogress', 'showcontinue', 'showfilters', 'activitymodal', 'sectiondates',
                'courseindex'] as $name
        ) {
            $this->assertArrayHasKey('label', $format->course_format_options(true)[$name], $name);
        }
        $this->assertSame(
            ['tilecolor', 'tileicon', 'groupname', 'duration'],
            array_keys($format->section_format_options(true))
        );
    }

    /**
     * Section names can be edited in place.
     */
    public function test_inplace_editable(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/external/externallib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 1]);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $result = \core_external::update_inplace_editable('format_coursify', 'sectionname', $section->id, 'Getting started');
        $this->assertSame('Getting started', $result['value']);
    }
}
