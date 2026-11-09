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

namespace format_formatium\output\courseformat;

use core_courseformat\output\local\content as content_base;
use format_formatium\local\tiles;
use renderer_base;

/**
 * Course page of the Formatium format.
 *
 * Outside edit mode the course page shows the general section and then the tiles; a section page shows the standard
 * section with links to the previous and next tiles. In edit mode everything is the standard course editor.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /** @var bool the "Add section" button is shown in edit mode */
    protected $hasaddsection = true;

    /**
     * Whether this is the course page shown as tiles.
     *
     * @return bool
     */
    protected function is_tiles_page(): bool {
        return !$this->format->get_sectionid() && !$this->format->show_editor();
    }

    /**
     * Template of the page.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_formatium/local/content';
    }

    /**
     * Data of the page.
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        if (!$this->is_tiles_page()) {
            $data = parent::export_for_template($output);
            $sectionid = $this->format->get_sectionid();
            if ($sectionid && !$this->format->show_editor()) {
                $data->formatiumnav = $this->export_section_navigation();
                $data->formatiumstrip = $this->export_section_strip($sectionid);
                (new tiles($this->format))->remember_section($sectionid);
            }
            return $data;
        }

        global $PAGE;
        $format = $this->format;
        $modinfo = $format->get_modinfo();
        $course = $format->get_course();
        $tiles = new tiles($format);

        $data = (object) [
            'title' => $format->page_title(),
            'format' => $format->get_format(),
            'sectionreturn' => 'null',
            'pagesectionid' => 'null',
            'tilesmode' => true,
            'tiles' => $tiles->export_tiles(),
            'columns' => (int) ($course->tilecolumns ?? 0),
        ];
        $data->hastiles = !empty($data->tiles);

        // The general section, when it has something to show.
        $general = $modinfo->get_section_info(0);
        if ($general && $format->is_section_visible($general)
                && (!empty($modinfo->sections[0]) || trim((string) $general->summary) !== '')) {
            $section = new $this->sectionclass($format, $general);
            $data->initialsection = $section->export_for_template($output);
        }

        if (!empty($course->showprogress)) {
            $data->overallprogress = $tiles->export_overall_progress();
        } else {
            foreach ($data->tiles as &$tile) {
                unset($tile['progress']);
            }
            unset($tile);
        }
        $next = $tiles->export_continue();
        foreach ($data->tiles as &$tile) {
            $tile['isnext'] = $next && $next['sectionid'] == $tile['id'];
        }
        unset($tile);
        if (!empty($course->showcontinue)) {
            $data->continue = $next;
            $data->coursecomplete = !$next && !empty($data->overallprogress['complete']);
            // Without completion tracking, "Continue" goes back to the last section the user visited.
            if (!$next && empty($data->overallprogress)) {
                $last = $tiles->export_last_section();
                if ($last) {
                    $data->continue = $last + ['issection' => true];
                }
            }
        }
        $data->hassummary = !empty($data->overallprogress) || !empty($data->continue) || !empty($data->coursecomplete);
        if (!empty($course->showbanner)) {
            $data->banner = $tiles->export_banner($output);
        }

        $layout = $course->layout ?? tiles::LAYOUT_GRID;
        $data->layout = array_key_exists($layout, tiles::layout_options()) ? $layout : tiles::LAYOUT_GRID;
        $data->isgrid = $data->layout === tiles::LAYOUT_GRID;
        $data->ispath = $data->layout === tiles::LAYOUT_PATH;
        $data->islist = $data->layout === tiles::LAYOUT_LIST;

        // Groups: a section with a group name starts one; tile titles go one heading level down inside named groups.
        $data->groups = tiles::group_tiles($data->tiles);
        $named = count(array_filter($data->groups, fn($group) => $group['hasname'])) > 0;
        foreach ($data->groups as &$group) {
            foreach ($group['tiles'] as &$tile) {
                $tile['headinglevel'] = $named ? 4 : 3;
            }
            unset($tile);
        }
        unset($group);

        // Filters: all, to do, done; only worth it when progress is shown and there are enough tiles.
        $data->showfilters = !empty($course->showfilters) && !empty($data->overallprogress) && count($data->tiles) > 3;
        if ($data->showfilters) {
            $PAGE->requires->js_call_amd('format_formatium/filters', 'init');
        }
        return $data;
    }

    /**
     * Mini tiles of every section, for the strip at the top of a section page.
     *
     * @param int $currentid id of the section being viewed
     * @return array
     */
    protected function export_section_strip(int $currentid): array {
        $tiles = (new tiles($this->format))->export_tiles();
        foreach ($tiles as &$tile) {
            $tile['iscurrentpage'] = $tile['id'] == $currentid;
        }
        unset($tile);
        return ['tiles' => $tiles, 'courseurl' => course_get_url($this->format->get_course())->out(false)];
    }

    /**
     * Links to the previous and next tiles, and back to the course, for a section page.
     *
     * @return array
     */
    protected function export_section_navigation(): array {
        $format = $this->format;
        $modinfo = $format->get_modinfo();
        $current = $modinfo->get_section_info_by_id($format->get_sectionid());
        $previous = null;
        $next = null;
        $found = false;
        foreach ($modinfo->get_listed_section_info_all() as $section) {
            if (!$section->section || !$section->uservisible) {
                continue;
            }
            if ($section->id == $current->id) {
                $found = true;
                continue;
            }
            if (!$found) {
                $previous = $section;
            } else {
                $next = $section;
                break;
            }
        }
        $link = function ($section) use ($format) {
            return $section ? [
                'url' => $format->get_view_url($section, ['navigation' => true])->out(false),
                'name' => $format->get_section_name($section),
            ] : null;
        };
        return [
            'courseurl' => course_get_url($format->get_course())->out(false),
            'previous' => $link($previous),
            'next' => $link($next),
        ];
    }
}
