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

namespace format_formatium\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use format_formatium\local\tiles;

/**
 * Privacy provider: the format keeps, as a user preference, the last section page of each course the user visited.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Data the plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(tiles::PREF_LASTSECTION . 'courseid', 'privacy:metadata:preference:lastsection');
        return $collection;
    }

    /**
     * Export the user preferences of the plugin.
     *
     * @param int $userid
     * @return void
     */
    public static function export_user_preferences(int $userid) {
        foreach (get_user_preferences(null, null, $userid) as $name => $value) {
            if (strpos($name, tiles::PREF_LASTSECTION) !== 0) {
                continue;
            }
            $courseid = (int) substr($name, strlen(tiles::PREF_LASTSECTION));
            writer::export_user_preference('format_formatium', $name, $value,
                get_string('privacy:lastsection', 'format_formatium', ['course' => $courseid, 'section' => $value]));
        }
    }
}
