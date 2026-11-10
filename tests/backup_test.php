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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Tests of the backup and restore of a Coursify course: options of the course and of the sections, and tile images.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_format_coursify_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_format_coursify_plugin::class)]
final class backup_test extends \advanced_testcase {
    /**
     * A course restored as a new course keeps its layout, the look of each tile and the tile images.
     */
    public function test_backup_restore(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 2,
            'layout' => tiles::LAYOUT_MAP, 'maptheme' => 'river']);
        $format = course_get_format($course);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $format->update_section_format_options(['id' => $section->id, 'tilecolor' => 'green', 'tileicon' => 'flask',
            'groupname' => 'Unit 1', 'duration' => 2]);
        $context = \context_course::instance($course->id);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'format_coursify',
            'filearea' => tiles::FILEAREA, 'itemid' => $section->id, 'filepath' => '/', 'filename' => 'tile.png'], 'image');

        $backup = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $backup->execute_plan();
        $file = $backup->get_results()['backup_destination'];
        $backup->destroy();

        $folder = 'coursify_backup_test';
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), make_backup_temp_directory($folder));
        $newid = \restore_dbops::create_new_course('Restored', 'restored', $course->category);
        $restore = new \restore_controller(
            $folder,
            $newid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $restore->execute_precheck();
        $restore->execute_plan();
        $restore->destroy();

        $newformat = course_get_format($newid);
        $this->assertSame('coursify', $newformat->get_format());
        $this->assertSame(tiles::LAYOUT_MAP, $newformat->get_format_options()['layout']);
        $this->assertSame('river', $newformat->get_format_options()['maptheme']);
        $newsection = get_fast_modinfo($newid)->get_section_info(1);
        $options = $newformat->get_format_options($newsection);
        $this->assertSame('green', $options['tilecolor']);
        $this->assertSame('flask', $options['tileicon']);
        $this->assertSame('Unit 1', $options['groupname']);
        $this->assertEquals(2, $options['duration']);
        $files = get_file_storage()->get_area_files(
            \context_course::instance($newid)->id,
            'format_coursify',
            tiles::FILEAREA,
            $newsection->id,
            'id',
            false
        );
        $this->assertCount(1, $files);
        $this->assertSame('tile.png', reset($files)->get_filename());
    }

    /**
     * Deleting a section deletes its tile image.
     */
    public function test_delete_section_deletes_image(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'numsections' => 2]);
        $section = get_fast_modinfo($course)->get_section_info(2);
        $context = \context_course::instance($course->id);
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'format_coursify',
            'filearea' => tiles::FILEAREA, 'itemid' => $section->id, 'filepath' => '/', 'filename' => 'tile.png'], 'image');

        $this->assertTrue(course_get_format($course)->delete_section($section, true));
        $this->assertTrue(get_file_storage()->is_area_empty($context->id, 'format_coursify', tiles::FILEAREA, $section->id));
    }
}
