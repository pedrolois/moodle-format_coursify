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
 * Restore of the Formatium course format: the tile images.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_formatium_plugin extends restore_format_plugin {
    /**
     * Path of the tile element of each section.
     *
     * @return restore_path_element[]
     */
    protected function define_section_plugin_structure() {
        return [new restore_path_element('formatium_tile', $this->get_pathfor('/'))];
    }

    /**
     * Restore the image of a tile into the new section.
     *
     * @param array $data
     * @return void
     */
    public function process_formatium_tile($data) {
        $oldsectionid = (int) ($data['sectionid'] ?? 0);
        $newsectionid = (int) $this->task->get_sectionid();
        if (!$oldsectionid || !$newsectionid) {
            return;
        }
        $this->set_mapping('formatium_tile', $oldsectionid, $newsectionid, true);
        $this->add_related_files('format_formatium', \format_formatium\local\tiles::FILEAREA, 'formatium_tile');
    }
}
