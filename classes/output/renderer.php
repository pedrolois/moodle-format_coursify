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

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Renderer of the Coursify course format.
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    /**
     * Constructor.
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        // Teachers who can only highlight sections still get the edit mode switch.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Section title, editable in place.
     *
     * @param \section_info|\stdClass $section
     * @param \stdClass $course
     * @return string
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Section title without a link, editable in place.
     *
     * @param \section_info|\stdClass $section
     * @param \stdClass $course
     * @return string
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }
}
