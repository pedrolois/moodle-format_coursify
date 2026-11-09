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

namespace format_formatium\local;

use cm_info;
use completion_info;
use core_availability\info as availability_info;
use core_courseformat\base as course_format;
use moodle_url;
use section_info;

/**
 * Data of the tiles of a course: look, progress, locks and the next activity to do.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tiles {
    /** File area of the tile images (item id = section id). */
    const FILEAREA = 'tileimage';

    /** Accent colours a tile can have (Bootstrap colours, so they follow the theme). */
    const COLORS = ['blue', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'green', 'teal', 'cyan', 'gray'];

    /** Font Awesome icons a tile can have. */
    const ICONS = [
        'book', 'book-open', 'graduation-cap', 'lightbulb', 'flask', 'calculator', 'code', 'laptop', 'chart-line',
        'comments', 'users', 'video', 'headphones', 'image', 'pen', 'file-lines', 'clipboard-check', 'puzzle-piece',
        'rocket', 'flag-checkered', 'globe', 'leaf', 'heart', 'star', 'trophy', 'compass', 'briefcase', 'gear',
    ];

    /** @var course_format */
    protected $format;

    /** @var completion_info|null completion of the course, null when not tracked for this user */
    protected $completion = null;

    /** @var \stored_file[] tile images by section id */
    protected $images = null;

    /**
     * Constructor.
     *
     * @param course_format $format
     */
    public function __construct(course_format $format) {
        global $USER;
        $this->format = $format;
        $course = $format->get_course();
        $completion = new completion_info($course);
        // Progress is shown only to the users whose completion is tracked (enrolled learners).
        if ($completion->is_enabled() && isloggedin() && !isguestuser() && $completion->is_tracked_user($USER->id)) {
            $this->completion = $completion;
        }
    }

    /**
     * Options of the number of columns.
     *
     * @return array
     */
    public static function column_options(): array {
        return [
            0 => get_string('tilecolumns_auto', 'format_formatium'),
            2 => 2,
            3 => 3,
            4 => 4,
        ];
    }

    /**
     * Options of the tile colour.
     *
     * @return array
     */
    public static function color_options(): array {
        $options = ['' => get_string('tilecolor_default', 'format_formatium')];
        foreach (self::COLORS as $color) {
            $options[$color] = get_string('color_' . $color, 'format_formatium');
        }
        return $options;
    }

    /**
     * Options of the tile icon.
     *
     * @return array
     */
    public static function icon_options(): array {
        $options = ['' => get_string('tileicon_none', 'format_formatium')];
        foreach (self::ICONS as $icon) {
            $options[$icon] = get_string('icon_' . str_replace('-', '', $icon), 'format_formatium');
        }
        return $options;
    }

    /**
     * Options of the tile image file manager.
     *
     * @return array
     */
    public static function image_options(): array {
        global $CFG;
        return [
            'maxfiles' => 1,
            'subdirs' => 0,
            'maxbytes' => $CFG->maxbytes,
            'accepted_types' => ['web_image'],
        ];
    }

    /**
     * Data of the tiles of the course (every listed section but the first one).
     *
     * @return array
     */
    public function export_tiles(): array {
        $modinfo = $this->format->get_modinfo();
        $tiles = [];
        foreach ($modinfo->get_listed_section_info_all() as $section) {
            if (!$section->section || !$this->format->is_section_visible($section)) {
                continue;
            }
            $tiles[] = $this->export_tile($section);
        }
        return $tiles;
    }

    /**
     * Data of one tile.
     *
     * @param section_info $section
     * @return array
     */
    public function export_tile(section_info $section): array {
        $course = $this->format->get_course();
        $context = \context_course::instance($course->id);
        $options = $this->format->get_format_options($section);
        $color = in_array($options['tilecolor'] ?? '', self::COLORS) ? $options['tilecolor'] : '';
        $icon = in_array($options['tileicon'] ?? '', self::ICONS) ? $options['tileicon'] : '';

        $summary = '';
        if ((string) $section->summary !== '') {
            $text = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section',
                $section->id);
            $text = format_text($text, $section->summaryformat, ['context' => $context]);
            $summary = shorten_text(trim(preg_replace('/\s+/u', ' ', html_to_text($text, 0, false))), 160);
        }

        $locked = !$section->uservisible;
        $lockinfo = '';
        if ($locked && !empty($section->availableinfo)) {
            $lockinfo = availability_info::format_info($section->availableinfo, $course);
        }

        $tile = [
            'id' => $section->id,
            'num' => $section->section,
            'name' => $this->format->get_section_name($section),
            'url' => $locked ? '' : $this->format->get_view_url($section, ['navigation' => true])->out(false),
            'summary' => $summary,
            'color' => $color ?: 'default',
            'icon' => $icon,
            'iscurrent' => $this->format->is_section_current($section),
            'locked' => $locked,
            'lockinfo' => $lockinfo,
            'hiddenfromstudents' => !$section->visible,
            'activities' => count($this->section_cms($section)),
        ];
        $image = $this->image_url($section);
        if ($image) {
            $tile['imageurl'] = $image->out(false);
        }
        if ($this->completion && !$locked) {
            $tile['progress'] = $this->progress($this->section_cms($section));
        }
        return $tile;
    }

    /**
     * Progress of the whole course.
     *
     * @return array|null null when completion is not tracked or there is nothing to complete
     */
    public function export_overall_progress(): ?array {
        if (!$this->completion) {
            return null;
        }
        $cms = [];
        foreach ($this->format->get_modinfo()->get_listed_section_info_all() as $section) {
            if ($section->uservisible) {
                $cms = array_merge($cms, $this->section_cms($section));
            }
        }
        $progress = $this->progress($cms);
        return $progress['total'] ? $progress : null;
    }

    /**
     * The next activity to do: the first visible one, in course order, with completion and not completed yet.
     *
     * @return array|null
     */
    public function export_continue(): ?array {
        if (!$this->completion) {
            return null;
        }
        foreach ($this->format->get_modinfo()->get_listed_section_info_all() as $section) {
            if (!$section->uservisible) {
                continue;
            }
            foreach ($this->section_cms($section) as $cm) {
                if ($cm->url && $this->tracked($cm) && !$this->completed($cm)) {
                    return [
                        'url' => $cm->url->out(false),
                        'name' => $cm->get_formatted_name(),
                        'section' => $this->format->get_section_name($cm->get_section_info()),
                        'iconurl' => $cm->get_icon_url()->out(false),
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Activities of a section the user can see, including those of its subsections.
     *
     * @param section_info $section
     * @return cm_info[]
     */
    protected function section_cms(section_info $section): array {
        $modinfo = $this->format->get_modinfo();
        $cms = [];
        foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            if (!$cm->uservisible || $cm->deletioninprogress) {
                continue;
            }
            $delegated = $cm->get_delegated_section_info();
            if ($delegated) {
                if ($delegated->uservisible) {
                    $cms = array_merge($cms, $this->section_cms($delegated));
                }
                continue;
            }
            $cms[] = $cm;
        }
        return $cms;
    }

    /**
     * Completion progress of some activities.
     *
     * @param cm_info[] $cms
     * @return array
     */
    protected function progress(array $cms): array {
        $total = 0;
        $done = 0;
        foreach ($cms as $cm) {
            if (!$this->tracked($cm)) {
                continue;
            }
            $total++;
            if ($this->completed($cm)) {
                $done++;
            }
        }
        $percent = $total ? (int) floor($done * 100 / $total) : 0;
        return [
            'total' => $total,
            'done' => $done,
            'percent' => $percent,
            'complete' => $total && $done == $total,
            'started' => $done > 0,
        ];
    }

    /**
     * Whether the completion of an activity is tracked.
     *
     * @param cm_info $cm
     * @return bool
     */
    protected function tracked(cm_info $cm): bool {
        return $this->completion && $this->completion->is_enabled($cm) != COMPLETION_TRACKING_NONE;
    }

    /**
     * Whether the user has completed an activity.
     *
     * @param cm_info $cm
     * @return bool
     */
    protected function completed(cm_info $cm): bool {
        $state = (int) $this->completion->get_data($cm, true)->completionstate;
        return $state === COMPLETION_COMPLETE || $state === COMPLETION_COMPLETE_PASS;
    }

    /**
     * URL of the image of a tile.
     *
     * @param section_info $section
     * @return moodle_url|null
     */
    protected function image_url(section_info $section): ?moodle_url {
        if ($this->images === null) {
            $this->images = [];
            $context = \context_course::instance($this->format->get_courseid());
            $files = get_file_storage()->get_area_files($context->id, 'format_formatium', self::FILEAREA, false,
                'itemid, filepath, filename', false);
            foreach ($files as $file) {
                if (!isset($this->images[$file->get_itemid()]) && $file->is_valid_image()) {
                    $this->images[$file->get_itemid()] = $file;
                }
            }
        }
        $file = $this->images[$section->id] ?? null;
        if (!$file) {
            return null;
        }
        return moodle_url::make_pluginfile_url($file->get_contextid(), 'format_formatium', self::FILEAREA,
            $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    }
}
