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
 * Site settings of the Coursify course format: defaults of new courses.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'format_coursify/defaults',
        get_string('settings_defaults', 'format_coursify'),
        get_string('settings_defaults_desc', 'format_coursify')
    ));
    $settings->add(new admin_setting_configselect(
        'format_coursify/layout',
        get_string('layout', 'format_coursify'),
        get_string('layout_help', 'format_coursify'),
        \format_coursify\local\tiles::LAYOUT_GRID,
        \format_coursify\local\tiles::layout_options()
    ));
    $settings->add(new admin_setting_configselect(
        'format_coursify/tilepalette',
        get_string('tilepalette', 'format_coursify'),
        get_string('tilepalette_help', 'format_coursify'),
        '',
        \format_coursify\local\tiles::palette_options()
    ));
    $settings->add(new admin_setting_configselect(
        'format_coursify/tilecolumns',
        get_string('tilecolumns', 'format_coursify'),
        get_string('tilecolumns_help', 'format_coursify'),
        0,
        \format_coursify\local\tiles::column_options()
    ));
    $settings->add(new admin_setting_configcheckbox(
        'format_coursify/showprogress',
        get_string('showprogress', 'format_coursify'),
        get_string('showprogress_help', 'format_coursify'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'format_coursify/showcontinue',
        get_string('showcontinue', 'format_coursify'),
        get_string('showcontinue_help', 'format_coursify'),
        1
    ));
}
