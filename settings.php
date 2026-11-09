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
 * Site settings of the Formatium course format: defaults of new courses.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'format_formatium/defaults',
        get_string('settings_defaults', 'format_formatium'),
        get_string('settings_defaults_desc', 'format_formatium')
    ));
    $settings->add(new admin_setting_configselect(
        'format_formatium/tilecolumns',
        get_string('tilecolumns', 'format_formatium'),
        get_string('tilecolumns_help', 'format_formatium'),
        0,
        \format_formatium\local\tiles::column_options()
    ));
    $settings->add(new admin_setting_configcheckbox(
        'format_formatium/showprogress',
        get_string('showprogress', 'format_formatium'),
        get_string('showprogress_help', 'format_formatium'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'format_formatium/showcontinue',
        get_string('showcontinue', 'format_formatium'),
        get_string('showcontinue_help', 'format_formatium'),
        1
    ));
}
