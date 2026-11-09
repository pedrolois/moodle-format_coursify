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

namespace format_formatium\form;

use format_formatium\local\tiles;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/editsection_form.php');

/**
 * Section edit form that also loads and saves the tile image.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editsection_form extends \editsection_form {
    /**
     * Load the current tile image into a draft area.
     *
     * @param \stdClass|array $defaultvalues
     * @return void
     */
    public function set_data($defaultvalues) {
        $defaultvalues = (object) $defaultvalues;
        $context = \context_course::instance($this->_customdata['course']->id);
        $defaultvalues = file_prepare_standard_filemanager(
            $defaultvalues,
            'tileimage',
            tiles::image_options(),
            $context,
            'format_formatium',
            tiles::FILEAREA,
            $defaultvalues->id ?? null
        );
        parent::set_data($defaultvalues);
    }

    /**
     * Save the tile image from the draft area.
     *
     * @return \stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data !== null && property_exists($data, 'tileimage_filemanager')) {
            $context = \context_course::instance($this->_customdata['course']->id);
            $data = file_postupdate_standard_filemanager(
                $data,
                'tileimage',
                tiles::image_options(),
                $context,
                'format_formatium',
                tiles::FILEAREA,
                $data->id
            );
            unset($data->tileimage_filemanager, $data->tileimage);
        }
        return $data;
    }
}
