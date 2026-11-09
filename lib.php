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
 * Formatium course format: the course is a grid of tiles, one per section.
 *
 * @package   format_formatium
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php');

use core\lang_string;
use format_formatium\local\tiles;

/**
 * Main class of the Formatium course format.
 *
 * Students see the course as tiles that open each section on its own page (course/section.php). In edit mode the
 * course page is the standard Moodle course editor, so drag and drop, bulk editing and subsections all keep working.
 */
class format_formatium extends core_courseformat\base {
    /**
     * The format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * The format shows the course index drawer.
     *
     * @return bool
     */
    public function uses_course_index() {
        $course = $this->get_course();
        return !isset($course->courseindex) || !empty($course->courseindex);
    }

    /**
     * Activities can be indented.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return true;
    }

    /**
     * Name of a section.
     *
     * @param int|stdClass|section_info $section
     * @return string
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string) $section->name !== '') {
            return format_string($section->name, true, ['context' => context_course::instance($this->courseid)]);
        }
        return $this->get_default_section_name($section);
    }

    /**
     * Default name of a section.
     *
     * @param int|stdClass|section_info $section
     * @return string
     */
    public function get_default_section_name($section) {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_formatium');
        }
        return get_string('newsection', 'format_formatium');
    }

    /**
     * Title of the course page.
     *
     * @return string
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * URL of a section: its own page, or the course page with an anchor in edit mode.
     *
     * @param int|stdClass|section_info|null $section
     * @param array $options
     * @return moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        $section = (is_null($section) || $section instanceof section_info) ? $section
            : $this->get_section($section, IGNORE_MISSING);

        if (array_key_exists('pagesectionid', $options)) {
            $pagesectionid = $options['pagesectionid'] ?? null;
            $pagesection = $pagesectionid ? $this->get_modinfo()->get_section_info_by_id($pagesectionid, IGNORE_MISSING) : null;
        } else if (array_key_exists('sr', $options)) {
            $pagesection = !is_null($options['sr']) ? $this->get_section($options['sr'], IGNORE_MISSING) : null;
        } else if ($options['navigation'] ?? false) {
            $pagesection = ($section && $section->get_component_instance()) ?
                $section->get_component_instance()->get_parent_section() : $section;
        } else {
            $pagesection = null;
        }

        if (is_null($pagesection)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        } else {
            $url = new moodle_url('/course/section.php', ['id' => $pagesection->id]);
        }
        if ($section && ($section->id != $pagesection?->id)) {
            $url->set_anchor('section-' . $section->section);
        }
        return $url;
    }

    /**
     * The format supports the AJAX course editor.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        return (object) ['capable' => true];
    }

    /**
     * The format uses the reactive course editor components.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Next and previous buttons on activity pages.
     *
     * @return bool
     */
    public static function uses_linear_navigation(): bool {
        return true;
    }

    /**
     * No blocks are added to new courses.
     *
     * @return array
     */
    public function get_default_blocks() {
        return [BLOCK_POS_LEFT => [], BLOCK_POS_RIGHT => []];
    }

    /**
     * The format shows the latest news.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Sections can be deleted.
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Activities can be available but not shown on the course page inside visible sections.
     *
     * @param stdClass|cm_info $cm
     * @param stdClass|section_info $section
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        return !$section->section || $section->visible;
    }

    /**
     * Options of the course (Course settings > Course format).
     *
     * @param bool $foreditform
     * @return array
     */
    public function course_format_options($foreditform = false) {
        static $options = false;
        $config = get_config('format_formatium');
        if ($options === false) {
            $options = [
                'layout' => ['default' => $config->layout ?? tiles::LAYOUT_GRID, 'type' => PARAM_ALPHA],
                'tilepalette' => ['default' => $config->tilepalette ?? '', 'type' => PARAM_ALPHA],
                'tilecolumns' => ['default' => (int) ($config->tilecolumns ?? 0), 'type' => PARAM_INT],
                'tilestyle' => ['default' => $config->tilestyle ?? tiles::STYLE_CARD, 'type' => PARAM_ALPHA],
                'maptheme' => ['default' => 'road', 'type' => PARAM_ALPHA],
                'numbering' => ['default' => tiles::NUMBERING_NUMBER, 'type' => PARAM_ALPHA],
                'showbanner' => ['default' => (int) ($config->showbanner ?? 1), 'type' => PARAM_BOOL],
                'bannerposition' => ['default' => 50, 'type' => PARAM_INT],
                'showprogress' => ['default' => (int) ($config->showprogress ?? 1), 'type' => PARAM_BOOL],
                'showcontinue' => ['default' => (int) ($config->showcontinue ?? 1), 'type' => PARAM_BOOL],
                'showfilters' => ['default' => 1, 'type' => PARAM_BOOL],
                'sectiondates' => ['default' => tiles::DATES_NONE, 'type' => PARAM_ALPHA],
                'courseindex' => ['default' => 1, 'type' => PARAM_BOOL],
            ];
        }
        if ($foreditform && !isset($options['layout']['label'])) {
            $yesno = [0 => new lang_string('no'), 1 => new lang_string('yes')];
            $edit = [
                'layout' => tiles::layout_options(),
                'tilepalette' => tiles::palette_options(),
                'tilecolumns' => tiles::column_options(),
                'tilestyle' => tiles::style_options(),
                'maptheme' => tiles::maptheme_options(),
                'numbering' => tiles::numbering_options(),
                'showbanner' => $yesno,
                'bannerposition' => tiles::position_options(),
                'showprogress' => $yesno,
                'showcontinue' => $yesno,
                'showfilters' => $yesno,
                'sectiondates' => tiles::dates_options(),
                'courseindex' => $yesno,
            ];
            foreach ($edit as $name => $choices) {
                $options[$name] += self::select_element($name, $choices);
            }
        }
        return $options;
    }

    /**
     * Definition of a select element of the edit forms.
     *
     * @param string $name option name, also the string of its label and help
     * @param array $choices
     * @return array
     */
    protected static function select_element(string $name, array $choices): array {
        return [
            'label' => new lang_string($name, 'format_formatium'),
            'element_type' => 'select',
            'element_attributes' => [$choices],
            'help' => $name,
            'help_component' => 'format_formatium',
        ];
    }

    /**
     * Options of each section (Edit section > Tile).
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        $options = [
            'tilecolor' => ['default' => '', 'type' => PARAM_ALPHA],
            'tileicon' => ['default' => '', 'type' => PARAM_ALPHANUMEXT],
            'groupname' => ['default' => '', 'type' => PARAM_TEXT],
            'duration' => ['default' => 1, 'type' => PARAM_INT],
        ];
        if ($foreditform) {
            $options['tilecolor'] += self::select_element('tilecolor', tiles::color_options());
            $options['tileicon'] += self::select_element('tileicon', tiles::icon_options());
            $options['groupname'] += [
                'label' => new lang_string('groupname', 'format_formatium'),
                'element_type' => 'text',
                'element_attributes' => [['size' => 30, 'maxlength' => 100]],
                'help' => 'groupname',
                'help_component' => 'format_formatium',
            ];
            $options['duration'] += self::select_element('duration', tiles::duration_options());
        }
        return $options;
    }

    /**
     * Elements of the course and section edit forms; sections also get the tile image.
     *
     * @param MoodleQuickForm $mform
     * @param bool $forsection
     * @return array
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        if ($forsection) {
            $mform->addElement('header', 'formatiumtile', get_string('tile', 'format_formatium'));
            $mform->setExpanded('formatiumtile', true);
        }
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if ($forsection) {
            $elements[] = $mform->addElement(
                'filemanager',
                'tileimage_filemanager',
                get_string('tileimage', 'format_formatium'),
                null,
                tiles::image_options()
            );
            $mform->addHelpButton('tileimage_filemanager', 'tileimage', 'format_formatium');
        } else if (empty($COURSE->id) || $COURSE->id == SITEID) {
            // New courses are created with the default number of sections.
            $element = $mform->addElement('hidden', 'numsections');
            $mform->setType('numsections', PARAM_INT);
            $mform->setDefault('numsections', get_config('moodlecourse')->numsections);
            array_unshift($elements, $element);
        }
        return $elements;
    }

    /**
     * Section edit form, which also saves the tile image.
     *
     * @param mixed $action
     * @param array $customdata
     * @return \format_formatium\form\editsection_form
     */
    public function editsection_form($action, $customdata = []) {
        global $CFG;
        require_once($CFG->dirroot . '/course/editsection_form.php');
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }
        return new \format_formatium\form\editsection_form($action, $customdata);
    }

    /**
     * Save the course options, keeping the current value of the ones not in the form.
     *
     * @param stdClass|array $data
     * @param stdClass|null $oldcourse
     * @return bool
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array) $data;
        if ($oldcourse !== null) {
            $oldcourse = (array) $oldcourse;
            foreach (array_keys($this->course_format_options()) as $key) {
                if (!array_key_exists($key, $data) && array_key_exists($key, $oldcourse)) {
                    $data[$key] = $oldcourse[$key];
                }
            }
        }
        $changed = $this->update_format_options($data);
        // A course coming from the Tiles format keeps its tile photos, icons and colour.
        if ($oldcourse !== null && ($oldcourse['format'] ?? '') === 'tiles') {
            \format_formatium\local\tiles_importer::import($this->courseid, $oldcourse);
        }
        return $changed;
    }

    /**
     * Delete the tile image with the section.
     *
     * @param int|stdClass|section_info $section
     * @param bool $forcedeleteifnotempty
     * @return bool
     */
    public function delete_section($section, $forcedeleteifnotempty = false) {
        $section = $this->get_section($section);
        $sectionid = (int) $section->id;
        $deleted = parent::delete_section($section, $forcedeleteifnotempty);
        if ($deleted) {
            get_file_storage()->delete_area_files(
                context_course::instance($this->courseid)->id,
                'format_formatium',
                tiles::FILEAREA,
                $sectionid
            );
        }
        return $deleted;
    }

    /**
     * Course format options for the mobile app and web services.
     *
     * @return array
     */
    public function get_config_for_external() {
        return $this->get_format_options();
    }

    /**
     * No legacy JavaScript is needed.
     *
     * @return array
     */
    public function get_required_jsfiles(): array {
        return [];
    }

    /**
     * Highlight and unhighlight sections from the classic section menu.
     *
     * @param stdClass|section_info $section
     * @param string $action
     * @param int $sr
     * @return null|array
     */
    public function section_action($section, $action, $sr) {
        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            $actions = \core_courseformat\formatactions::section($this->courseid);
            if ($action === 'setmarker') {
                $actions->set_marker(get_fast_modinfo($this->courseid)->get_section_info($section->section), true);
            } else {
                $actions->remove_all_markers();
            }
            return null;
        }
        return parent::section_action($section, $action, $sr);
    }
}

/**
 * Update a section name edited in place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable|null
 */
function format_formatium_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'formatium'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
    return null;
}

/**
 * Serve the tile images.
 *
 * @param stdClass $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false when the file is not found
 */
function format_formatium_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_COURSE || $filearea !== tiles::FILEAREA) {
        return false;
    }
    require_course_login($course);
    $sectionid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'format_formatium', $filearea, $sectionid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}
