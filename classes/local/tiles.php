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

    /** Layout: grid of tiles. */
    const LAYOUT_GRID = 'grid';

    /** Layout: learning path, one step after another. */
    const LAYOUT_PATH = 'path';

    /** Layout: compact list. */
    const LAYOUT_LIST = 'list';

    /** Palettes: colours given in turn to the tiles that have no colour of their own. */
    const PALETTES = [
        'rainbow' => ['blue', 'purple', 'pink', 'red', 'orange', 'yellow', 'green', 'teal', 'cyan', 'indigo'],
        'ocean' => ['blue', 'cyan', 'teal', 'indigo'],
        'sunset' => ['orange', 'red', 'pink', 'purple'],
        'forest' => ['green', 'teal', 'yellow', 'cyan'],
        'calm' => ['indigo', 'purple', 'blue', 'gray'],
    ];

    /** Font Awesome icons a tile can have. */
    const ICONS = [
        'book', 'book-open', 'graduation-cap', 'lightbulb', 'flask', 'calculator', 'code', 'laptop', 'chart-line',
        'comments', 'users', 'video', 'headphones', 'image', 'pen', 'file-lines', 'clipboard-check', 'puzzle-piece',
        'rocket', 'flag-checkered', 'globe', 'leaf', 'heart', 'star', 'trophy', 'compass', 'briefcase', 'gear',
    ];

    /** Tile style: card with the image on top and the text below. */
    const STYLE_CARD = 'card';

    /** Tile style: the image fills the tile, with the title on a coloured band. */
    const STYLE_PHOTO = 'photo';

    /** Numbering: 1, 2, 3. */
    const NUMBERING_NUMBER = 'number';

    /** Numbering: A, B, C. */
    const NUMBERING_LETTER = 'letter';

    /** Numbering: I, II, III. */
    const NUMBERING_ROMAN = 'roman';

    /** Section dates: none. */
    const DATES_NONE = 'none';

    /** Section dates: one after another from the course start, each lasting its duration in weeks. */
    const DATES_WEEKLY = 'weekly';

    /** User preference with the last section page the user visited in a course (suffix: course id). */
    const PREF_LASTSECTION = 'format_formatium_lastsection_';

    /** @var course_format */
    protected $format;

    /** @var array|null dates of the sections: section id => [start, end] */
    protected $dates = null;

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
     * Options of the layout of the course page.
     *
     * @return array
     */
    public static function layout_options(): array {
        return [
            self::LAYOUT_GRID => get_string('layout_grid', 'format_formatium'),
            self::LAYOUT_PATH => get_string('layout_path', 'format_formatium'),
            self::LAYOUT_LIST => get_string('layout_list', 'format_formatium'),
        ];
    }

    /**
     * Options of the palette.
     *
     * @return array
     */
    public static function palette_options(): array {
        $options = ['' => get_string('palette_none', 'format_formatium')];
        foreach (array_keys(self::PALETTES) as $palette) {
            $options[$palette] = get_string('palette_' . $palette, 'format_formatium');
        }
        return $options;
    }

    /**
     * Options of the style of the tiles of the grid.
     *
     * @return array
     */
    public static function style_options(): array {
        return [
            self::STYLE_CARD => get_string('tilestyle_card', 'format_formatium'),
            self::STYLE_PHOTO => get_string('tilestyle_photo', 'format_formatium'),
        ];
    }

    /**
     * Options of the numbering style.
     *
     * @return array
     */
    public static function numbering_options(): array {
        return [
            self::NUMBERING_NUMBER => get_string('numbering_number', 'format_formatium'),
            self::NUMBERING_LETTER => get_string('numbering_letter', 'format_formatium'),
            self::NUMBERING_ROMAN => get_string('numbering_roman', 'format_formatium'),
        ];
    }

    /**
     * Options of the vertical position of the banner image.
     *
     * @return array
     */
    public static function position_options(): array {
        return [
            0 => get_string('position_top', 'format_formatium'),
            25 => get_string('position_upper', 'format_formatium'),
            50 => get_string('position_middle', 'format_formatium'),
            75 => get_string('position_lower', 'format_formatium'),
            100 => get_string('position_bottom', 'format_formatium'),
        ];
    }

    /**
     * Options of the section dates.
     *
     * @return array
     */
    public static function dates_options(): array {
        return [
            self::DATES_NONE => get_string('sectiondates_none', 'format_formatium'),
            self::DATES_WEEKLY => get_string('sectiondates_weekly', 'format_formatium'),
        ];
    }

    /**
     * Options of the duration of a section, in weeks.
     *
     * @return array
     */
    public static function duration_options(): array {
        $options = [0 => get_string('duration_none', 'format_formatium')];
        for ($weeks = 1; $weeks <= 6; $weeks++) {
            $options[$weeks] = get_string('numweeks', 'moodle', $weeks);
        }
        $options[1] = get_string('oneweek', 'format_formatium');
        return $options;
    }

    /**
     * A number written in the numbering style of the course.
     *
     * @param int $number
     * @param string $style
     * @return string
     */
    public static function format_number(int $number, string $style): string {
        if ($style === self::NUMBERING_LETTER) {
            $label = '';
            for ($n = $number; $n > 0; $n = intdiv($n - 1, 26)) {
                $label = chr(65 + ($n - 1) % 26) . $label;
            }
            return $label;
        }
        if ($style === self::NUMBERING_ROMAN) {
            $numerals = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50,
                'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
            $label = '';
            foreach ($numerals as $numeral => $value) {
                while ($number >= $value) {
                    $label .= $numeral;
                    $number -= $value;
                }
            }
            return $label;
        }
        return (string) $number;
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
        $course = $this->format->get_course();
        $palette = self::PALETTES[$course->tilepalette ?? ''] ?? [];
        $numbering = $course->numbering ?? self::NUMBERING_NUMBER;
        $tiles = [];
        foreach ($modinfo->get_listed_section_info_all() as $section) {
            if (!$section->section || !$this->format->is_section_visible($section)) {
                continue;
            }
            $tile = $this->export_tile($section);
            if ($palette && $tile['color'] === 'default') {
                $tile['color'] = $palette[count($tiles) % count($palette)];
            }
            $tile['step'] = count($tiles) + 1;
            $tile['steplabel'] = self::format_number($tile['step'], $numbering);
            $tiles[] = $tile;
        }
        $total = count($tiles);
        $steps = self::format_number($total, $numbering);
        foreach ($tiles as $i => $tile) {
            $tiles[$i]['steps'] = $steps;
            $tiles[$i]['islast'] = $i == $total - 1;
            $tiles[$i]['side'] = $i % 2 ? 'end' : 'start';
        }
        return $tiles;
    }

    /**
     * Tiles split into groups: a section with a group name starts a new group.
     *
     * @param array $tiles from export_tiles()
     * @return array groups, each with its name, tiles and how many of them are complete
     */
    public static function group_tiles(array $tiles): array {
        $groups = [];
        $current = null;
        foreach ($tiles as $tile) {
            if ($current === null || $tile['groupname'] !== '') {
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = ['name' => $tile['groupname'], 'tiles' => [], 'done' => 0, 'count' => 0];
            }
            $current['tiles'][] = $tile;
            $current['count']++;
            if (!empty($tile['progress']['complete'])) {
                $current['done']++;
            }
        }
        if ($current !== null) {
            $groups[] = $current;
        }
        foreach ($groups as $i => $group) {
            $groups[$i]['id'] = 'formatium-group-' . $i;
            $groups[$i]['hasname'] = $group['name'] !== '';
        }
        return $groups;
    }

    /**
     * Dates of the sections when the course gives them dates: one after another from the course start.
     *
     * @return array section id => [start, end] (no entry for sections without time)
     */
    protected function section_dates(): array {
        if ($this->dates !== null) {
            return $this->dates;
        }
        $this->dates = [];
        $course = $this->format->get_course();
        if (($course->sectiondates ?? self::DATES_NONE) !== self::DATES_WEEKLY || empty($course->startdate)) {
            return $this->dates;
        }
        $start = usergetmidnight($course->startdate);
        foreach ($this->format->get_modinfo()->get_listed_section_info_all() as $section) {
            if (!$section->section) {
                continue;
            }
            $weeks = (int) ($this->format->get_format_options($section)['duration'] ?? 1);
            if ($weeks <= 0) {
                continue;
            }
            $end = $start + $weeks * WEEKSECS;
            $this->dates[$section->id] = [$start, $end - 1];
            $start = $end;
        }
        return $this->dates;
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
            'groupname' => format_string(trim((string) ($options['groupname'] ?? '')), true, ['context' => $context]),
        ];

        // Dates: a date range, "This week" (which is also the current section when the teacher set none) and "Opens on".
        $dates = $this->section_dates()[$section->id] ?? null;
        if ($dates) {
            [$start, $end] = $dates;
            $format = get_string('strftimedateshort', 'langconfig');
            $tile['daterange'] = get_string('daterange', 'format_formatium',
                ['start' => userdate($start, $format), 'end' => userdate($end, $format)]);
            $now = time();
            $tile['isthisweek'] = $now >= $start && $now <= $end;
            if ($start > $now) {
                $tile['opens'] = userdate($start, $format);
            }
            if (empty($course->marker) && $tile['isthisweek']) {
                $tile['iscurrent'] = true;
            }
        }
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
                        'section' => $this->format->get_section_name($section),
                        'sectionid' => $section->id,
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
        $complete = $total && $done == $total;
        // A label that changes with the progress: start, how many are done, done.
        if (!$total) {
            $label = '';
        } else if ($complete) {
            $label = get_string('progress_done', 'format_formatium');
        } else if (!$done) {
            $label = get_string('progress_start', 'format_formatium');
        } else {
            $label = get_string('progress_some', 'format_formatium', ['done' => $done, 'total' => $total]);
        }
        return [
            'total' => $total,
            'done' => $done,
            'percent' => $percent,
            'complete' => $complete,
            'started' => $done > 0,
            'label' => $label,
        ];
    }

    /**
     * The section page the user visited last, when the user can still see it.
     *
     * @return array|null
     */
    public function export_last_section(): ?array {
        if (!isloggedin() || isguestuser()) {
            return null;
        }
        $sectionid = (int) get_user_preferences(self::PREF_LASTSECTION . $this->format->get_courseid(), 0);
        $section = $sectionid ? $this->format->get_modinfo()->get_section_info_by_id($sectionid) : null;
        if (!$section || !$section->uservisible || !$section->section) {
            return null;
        }
        return [
            'url' => $this->format->get_view_url($section, ['navigation' => true])->out(false),
            'name' => $this->format->get_section_name($section),
            'sectionid' => $section->id,
        ];
    }

    /**
     * Remember the section page the user is visiting.
     *
     * @param int $sectionid
     * @return void
     */
    public function remember_section(int $sectionid): void {
        if (!isloggedin() || isguestuser()) {
            return;
        }
        $name = self::PREF_LASTSECTION . $this->format->get_courseid();
        if ((int) get_user_preferences($name, 0) !== $sectionid) {
            set_user_preference($name, $sectionid);
        }
    }

    /**
     * Data of the course banner: image, focal point, summary and contacts.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_banner(\renderer_base $output): array {
        $course = $this->format->get_course();
        $element = new \core_course_list_element($course);
        $imageurl = '';
        foreach ($element->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                    $file->get_filearea(), null, $file->get_filepath(), $file->get_filename())->out(false);
                break;
            }
        }
        $context = \context_course::instance($course->id);
        $summary = '';
        if ((string) $course->summary !== '') {
            $text = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
            $text = format_text($text, $course->summaryformat, ['context' => $context]);
            $summary = shorten_text(trim(preg_replace('/\s+/u', ' ', html_to_text($text, 0, false))), 220);
        }
        global $DB;
        $contacts = [];
        $list = array_slice($element->get_course_contacts(), 0, 4, true);
        $users = [];
        if ($list) {
            // The contacts come without the fields of their pictures.
            $fields = \core_user\fields::for_userpic()->get_sql('', false, '', '', false)->selects;
            [$insql, $params] = $DB->get_in_or_equal(array_keys($list));
            $users = $DB->get_records_select('user', "id $insql", $params, '', $fields);
        }
        foreach ($list as $userid => $contact) {
            $user = $users[$userid] ?? null;
            if (!$user) {
                continue;
            }
            $contacts[] = [
                'name' => $contact['username'],
                'role' => $contact['rolename'],
                'url' => (new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]))->out(false),
                'picture' => $output->user_picture($user, ['size' => 36, 'link' => false, 'alttext' => false]),
            ];
        }
        return [
            'imageurl' => $imageurl ?: $output->get_generated_image_for_id($course->id),
            'hasimage' => $imageurl !== '',
            'position' => max(0, min(100, (int) ($course->bannerposition ?? 50))),
            'summary' => $summary,
            'contacts' => $contacts,
            'hascontacts' => !empty($contacts),
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
