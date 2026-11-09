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

namespace format_coursify\local;

/**
 * Brings the look of a course that used the Tiles format (format_tiles): tile photos, icons and base colour.
 *
 * Nothing of the original course is changed or removed, so the course can go back to Tiles.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tiles_importer {
    /** Tiles option type of a section photo. */
    const TILES_PHOTO = 1;

    /** Tiles option type of a section icon. */
    const TILES_ICON = 3;

    /** Tiles icons => Coursify icons. */
    const ICON_MAP = [
        'address-book-o' => 'users',
        'assessment_graded' => 'clipboard-check',
        'assessment_timer' => 'clipboard-check',
        'award-solid' => 'trophy',
        'bar-chart' => 'chart-line',
        'book' => 'book',
        'bookmark-o' => 'book-open',
        'briefcase' => 'briefcase',
        'calculator' => 'calculator',
        'check' => 'clipboard-check',
        'comment-o' => 'comments',
        'comments-o' => 'comments',
        'compass' => 'compass',
        'file-text-o' => 'file-lines',
        'film' => 'video',
        'flag-checkered' => 'flag-checkered',
        'flask' => 'flask',
        'globe' => 'globe',
        'handshake-o' => 'users',
        'headphones' => 'headphones',
        'heartbeat' => 'heart',
        'jigsaw' => 'puzzle-piece',
        'laptop' => 'laptop',
        'lightbulb-o' => 'lightbulb',
        'line-chart' => 'chart-line',
        'map-o' => 'compass',
        'map-signs' => 'compass',
        'mortar-board' => 'graduation-cap',
        'newspaper-o' => 'file-lines',
        'pencil-square-o' => 'pen',
        'pie-chart' => 'chart-line',
        'podcast' => 'headphones',
        'puzzle-piece' => 'puzzle-piece',
        'sliders' => 'gear',
        'star' => 'star',
        'star-half-o' => 'star',
        'star-o' => 'star',
        'survey' => 'clipboard-check',
        'tasks' => 'clipboard-check',
        'television' => 'video',
        'thinking-person' => 'lightbulb',
        'trophy' => 'trophy',
        'university' => 'graduation-cap',
        'user-o' => 'users',
        'users' => 'users',
        'volume-up' => 'headphones',
        'wrench' => 'gear',
    ];

    /** Bootstrap colours of the palette, to find the closest one to a Tiles hex colour. */
    const COLOR_HEX = [
        'blue' => '#0d6efd', 'indigo' => '#6610f2', 'purple' => '#6f42c1', 'pink' => '#d63384', 'red' => '#dc3545',
        'orange' => '#fd7e14', 'yellow' => '#ffc107', 'green' => '#198754', 'teal' => '#20c997', 'cyan' => '#0dcaf0',
        'gray' => '#6c757d',
    ];

    /** @var array counts of the last import, for reports */
    public static $lastcounts = ['photos' => 0, 'icons' => 0, 'colours' => 0];

    /**
     * Import the look of a former Tiles course.
     *
     * @param int $courseid
     * @param array $tilescourse course format options the course had with Tiles (basecolour, defaulttileicon)
     * @return array counts of what was imported: photos, icons, colours
     */
    public static function import(int $courseid, array $tilescourse = []): array {
        global $DB;
        $counts = ['photos' => 0, 'icons' => 0, 'colours' => 0];
        $format = course_get_format($courseid);
        $modinfo = get_fast_modinfo($courseid);
        $sectionids = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $sectionids[$section->id] = $section;
        }

        // Icons, from the Tiles options table (when Tiles is still installed).
        $icons = [];
        if ($DB->get_manager()->table_exists('format_tiles_tile_options')) {
            $records = $DB->get_records('format_tiles_tile_options', ['courseid' => $courseid, 'optiontype' => self::TILES_ICON]);
            foreach ($records as $record) {
                $icons[$record->elementid] = $record->optionvalue;
            }
        }
        $defaulticon = self::ICON_MAP[$tilescourse['defaulttileicon'] ?? ''] ?? '';
        $colour = self::closest_colour((string) ($tilescourse['basecolour'] ?? ''));

        foreach ($sectionids as $sectionid => $section) {
            if (!$section->section || $section->is_delegated()) {
                continue;
            }
            $current = $format->get_format_options($section);
            $update = ['id' => $sectionid];
            $icon = self::ICON_MAP[$icons[$sectionid] ?? ''] ?? $defaulticon;
            if ($icon && empty($current['tileicon'])) {
                $update['tileicon'] = $icon;
                $counts['icons']++;
            }
            if ($colour && empty($current['tilecolor'])) {
                $update['tilecolor'] = $colour;
                $counts['colours']++;
            }
            if (count($update) > 1) {
                $format->update_section_format_options($update);
            }
        }

        // Photos: copied from the Tiles file area of each section, when the section has no image yet.
        $context = \context_course::instance($courseid);
        $fs = get_file_storage();
        $photos = $fs->get_area_files($context->id, 'format_tiles', 'tilephoto', false, 'itemid, filepath, filename', false);
        $done = [];
        foreach ($photos as $photo) {
            $sectionid = (int) $photo->get_itemid();
            if (!isset($sectionids[$sectionid]) || isset($done[$sectionid]) || !$photo->is_valid_image()) {
                continue;
            }
            $done[$sectionid] = true;
            if (!$fs->is_area_empty($context->id, 'format_coursify', tiles::FILEAREA, $sectionid)) {
                continue;
            }
            $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => 'format_coursify',
                'filearea' => tiles::FILEAREA,
                'itemid' => $sectionid,
                'filepath' => '/',
            ], $photo);
            $counts['photos']++;
        }
        self::$lastcounts = $counts;
        return $counts;
    }

    /**
     * Palette colour closest to a hex colour.
     *
     * @param string $hex
     * @return string palette colour, or '' when the hex colour is not valid
     */
    public static function closest_colour(string $hex): string {
        $rgb = self::rgb($hex);
        if (!$rgb) {
            return '';
        }
        $best = '';
        $bestdistance = PHP_INT_MAX;
        foreach (self::COLOR_HEX as $name => $palettehex) {
            [$r, $g, $b] = self::rgb($palettehex);
            $distance = ($r - $rgb[0]) ** 2 + ($g - $rgb[1]) ** 2 + ($b - $rgb[2]) ** 2;
            if ($distance < $bestdistance) {
                $best = $name;
                $bestdistance = $distance;
            }
        }
        return $best;
    }

    /**
     * Red, green and blue of a hex colour.
     *
     * @param string $hex
     * @return int[]|null
     */
    protected static function rgb(string $hex): ?array {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return null;
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
