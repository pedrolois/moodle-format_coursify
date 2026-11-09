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

namespace format_coursify\output;

use format_coursify\local\tiles;

/**
 * Moodle app support: the data and template of the course page.
 *
 * The app gives the template the sections with their activities; this adds the look of each tile (colour, icon,
 * image, number, group), its progress and lock, and the next activity to do.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Course page of the app.
     *
     * @param array $args courseid, userid, applang, appversioncode...
     * @return array
     */
    public static function mobile_course_view(array $args): array {
        global $OUTPUT;
        $courseid = (int) $args['courseid'];
        // Access to the course: enrolled, guest access or the capability to view it, as on the website.
        require_login($courseid, false, null, false, true);

        $format = course_get_format($courseid);
        $course = $format->get_course();
        $tiles = new tiles($format);

        $bysection = [];
        foreach ($tiles->export_tiles() as $tile) {
            if (empty($course->showprogress)) {
                unset($tile['progress']);
            }
            $bysection[$tile['id']] = [
                'color' => $tile['color'],
                'icon' => $tile['icon'],
                'image' => $tile['imageurl'] ?? '',
                'number' => $tile['steplabel'],
                'summary' => $tile['summary'],
                'locked' => $tile['locked'],
                'lockinfo' => trim(preg_replace('/\s+/u', ' ', strip_tags($tile['lockinfo']))),
                'current' => !empty($tile['iscurrent']),
                'thisweek' => !empty($tile['isthisweek']),
                'dates' => $tile['daterange'] ?? '',
                'group' => $tile['groupname'],
                'progress' => $tile['progress']['percent'] ?? -1,
                'progresslabel' => $tile['progress']['label'] ?? '',
                'complete' => !empty($tile['progress']['complete']),
                'activities' => $tile['activities'],
            ];
        }

        $next = $tiles->export_continue();
        if ($next && isset($bysection[$next['sectionid']])) {
            $bysection[$next['sectionid']]['next'] = true;
        }
        $progress = !empty($course->showprogress) ? $tiles->export_overall_progress() : null;

        $data = [
            'tiles' => $bysection,
            'progress' => $progress ? $progress['percent'] : -1,
            'progressdone' => $progress['done'] ?? 0,
            'progresstotal' => $progress['total'] ?? 0,
            'continueurl' => (!empty($course->showcontinue) && $next) ? $next['url'] : '',
            'continuename' => $next['name'] ?? '',
            'complete' => !$next && !empty($progress['complete']),
            'selected' => 0,
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('format_coursify/mobile_course', []),
                ],
            ],
            'javascript' => '',
            'otherdata' => [
                'coursify' => json_encode($data),
            ],
        ];
    }
}
