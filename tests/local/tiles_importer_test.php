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

/**
 * Tests of the import of the look of a course that used the Tiles format.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify\local\tiles_importer::class)]
final class tiles_importer_test extends \advanced_testcase {
    /**
     * Hex colours map to the closest colour of the palette.
     */
    public function test_closest_colour(): void {
        $this->assertSame('blue', tiles_importer::closest_colour('#1670CC'));
        $this->assertSame('green', tiles_importer::closest_colour('#7A9A01'));
        $this->assertSame('red', tiles_importer::closest_colour('#D13C3C'));
        $this->assertSame('purple', tiles_importer::closest_colour('#9b4dca'));
        $this->assertSame('red', tiles_importer::closest_colour('d33'));
        $this->assertSame('', tiles_importer::closest_colour('not a colour'));
        $this->assertSame('', tiles_importer::closest_colour(''));
    }

    /**
     * The photos, the default icon and the base colour of the course come over; options already set are kept.
     */
    public function test_import(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 3]);
        $modinfo = get_fast_modinfo($course);
        $format = course_get_format($course);
        $section1 = $modinfo->get_section_info(1);
        $section2 = $modinfo->get_section_info(2);
        $format->update_section_format_options(['id' => $section2->id, 'tilecolor' => 'orange']);

        // A Tiles photo in section 1.
        $context = \context_course::instance($course->id);
        $image = imagecreatetruecolor(30, 20);
        ob_start();
        imagepng($image);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'format_tiles',
            'filearea' => 'tilephoto', 'itemid' => $section1->id, 'filepath' => '/tilephoto/', 'filename' => 'photo.png'],
            ob_get_clean());

        $counts = tiles_importer::import($course->id, ['basecolour' => '#1670CC', 'defaulttileicon' => 'mortar-board']);
        $this->assertSame(1, $counts['photos']);
        $this->assertSame(3, $counts['icons']);
        $this->assertSame(2, $counts['colours']);
        $this->assertSame($counts, tiles_importer::$lastcounts);

        $format = course_get_format($course->id);
        $this->assertSame('blue', $format->get_format_options($section1)['tilecolor']);
        $this->assertSame('graduation-cap', $format->get_format_options($section1)['tileicon']);
        $this->assertSame('orange', $format->get_format_options($section2)['tilecolor']);
        $this->assertFalse(get_file_storage()->is_area_empty($context->id, 'format_coursify', tiles::FILEAREA, $section1->id));

        // A second import changes nothing.
        $again = tiles_importer::import($course->id, ['basecolour' => '#1670CC', 'defaulttileicon' => 'mortar-board']);
        $this->assertSame(['photos' => 0, 'icons' => 0, 'colours' => 0], $again);
    }

    /**
     * Switching a course from Tiles to Coursify runs the import.
     */
    public function test_import_on_format_switch(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'topics', 'numsections' => 2]);
        // As if the course came from Tiles: the old options are what update_course() passes as the old course.
        $old = (array) course_get_format($course)->get_course();
        $old['format'] = 'tiles';
        $old['basecolour'] = '#D13C3C';
        update_course((object) ['id' => $course->id, 'format' => 'coursify']);
        course_get_format($course->id)->update_course_format_options([], (object) $old);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $this->assertSame('red', course_get_format($course->id)->get_format_options($section)['tilecolor']);
    }
}
