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
 * Backup of the Coursify course format: the tile images.
 *
 * The colour and icon of each tile are section format options, which the core backup already includes.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_coursify_plugin extends backup_format_plugin {
    /**
     * The tile image of each section.
     *
     * @return backup_plugin_element
     */
    protected function define_section_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'coursify');
        // The restore only processes elements with at least one field, so the section number goes with the id.
        $tile = new backup_nested_element($this->get_recommended_name(), ['sectionid'], ['sectionnum']);
        $tile->set_source_sql(
            'SELECT id AS sectionid, section AS sectionnum FROM {course_sections} WHERE id = ?',
            [backup::VAR_SECTIONID]
        );
        $tile->annotate_files('format_coursify', \format_coursify\local\tiles::FILEAREA, 'sectionid');
        $plugin->add_child($tile);
        return $plugin;
    }
}
