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
 * Moodle app support: the course page as tiles, with progress and Continue.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'format_coursify' => [
        'handlers' => [
            'coursify' => [
                'delegate' => 'CoreCourseFormatDelegate',
                'method' => 'mobile_course_view',
                'styles' => [
                    'url' => $CFG->wwwroot . '/course/format/coursify/mobile.css',
                    'version' => 2026110907,
                ],
                'canviewallsections' => false,
                'displaycourseindex' => true,
            ],
        ],
        'lang' => [
            ['activitiescount', 'format_coursify'],
            ['activitiesdone', 'format_coursify'],
            ['alltiles', 'format_coursify'],
            ['continue', 'format_coursify'],
            ['coursecomplete', 'format_coursify'],
            ['courseprogress', 'format_coursify'],
            ['current', 'format_coursify'],
            ['locked', 'format_coursify'],
            ['next', 'format_coursify'],
            ['notiles', 'format_coursify'],
            ['previous', 'format_coursify'],
            ['resume', 'format_coursify'],
            ['thisweek', 'format_coursify'],
            ['upnext', 'format_coursify'],
        ],
    ],
];
