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

namespace format_coursify\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use format_coursify\local\tiles;

/**
 * Tests of the privacy provider: the last section visited in each course is declared and exported.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify\privacy\provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The user preference is declared.
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('format_coursify'))->get_collection();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(\core_privacy\local\metadata\types\user_preference::class, reset($items));
    }

    /**
     * Only the preferences of the user, and only those of the format, are exported.
     */
    public function test_export_user_preferences(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        set_user_preference(tiles::PREF_LASTSECTION . '12', 345, $user);
        set_user_preference(tiles::PREF_LASTSECTION . '12', 999, $other);
        set_user_preference('some_other_preference', 'x', $user);

        provider::export_user_preferences($user->id);
        $preferences = writer::with_context(\context_system::instance())->get_user_preferences('format_coursify');
        $this->assertCount(1, (array) $preferences);
        $name = tiles::PREF_LASTSECTION . '12';
        $this->assertEquals(345, $preferences->$name->value);
    }
}
