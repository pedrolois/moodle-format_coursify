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

namespace format_coursify\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Content of an activity to show in a window over the course page: a page, a PDF file or a video link.
 *
 * Opening it counts as viewing the activity (the module's own view function logs it and updates its completion).
 *
 * @package   format_coursify
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_content extends external_api {
    /** Content shown as HTML. */
    const TYPE_HTML = 'html';

    /** Content shown in a frame (videos). */
    const TYPE_EMBED = 'embed';

    /** PDF file. */
    const TYPE_PDF = 'pdf';

    /** Not shown in a window: the activity opens as usual. */
    const TYPE_NONE = 'none';

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    /**
     * Content of the activity.
     *
     * @param int $cmid
     * @return array
     */
    public static function execute(int $cmid): array {
        global $DB, $CFG;
        ['cmid' => $cmid] = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        [$course, $cm] = get_course_and_cm_from_cmid($cmid);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $result = ['type' => self::TYPE_NONE, 'title' => $cm->get_formatted_name(), 'html' => '', 'url' => '',
            'viewurl' => $cm->url ? $cm->url->out(false) : ''];

        if (
            course_get_format($course)->get_format() !== 'coursify' || !$cm->uservisible
                || !in_array($cm->modname, ['page', 'url', 'resource'])
        ) {
            return $result;
        }
        require_capability('mod/' . $cm->modname . ':view', $context);
        $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);

        if ($cm->modname === 'page') {
            require_once($CFG->dirroot . '/mod/page/lib.php');
            $content = file_rewrite_pluginfile_urls(
                $instance->content,
                'pluginfile.php',
                $context->id,
                'mod_page',
                'content',
                $instance->revision
            );
            $result['type'] = self::TYPE_HTML;
            $result['html'] = format_text($content, $instance->contentformat, ['context' => $context, 'noclean' => true]);
            page_view($instance, $course, $cm, $context);
        } else if ($cm->modname === 'url') {
            require_once($CFG->dirroot . '/mod/url/lib.php');
            require_once($CFG->dirroot . '/mod/url/locallib.php');
            $embed = self::video_embed_url(url_get_full_url($instance, $cm, $course));
            if ($embed === '') {
                return $result;
            }
            $result['type'] = self::TYPE_EMBED;
            $result['url'] = $embed;
            url_view($instance, $course, $cm, $context);
        } else {
            require_once($CFG->dirroot . '/mod/resource/lib.php');
            $files = get_file_storage()->get_area_files(
                $context->id,
                'mod_resource',
                'content',
                0,
                'sortorder DESC, id ASC',
                false
            );
            $file = reset($files);
            if (!$file || $file->get_mimetype() !== 'application/pdf') {
                return $result;
            }
            $result['type'] = self::TYPE_PDF;
            $result['url'] = \moodle_url::make_pluginfile_url(
                $context->id,
                'mod_resource',
                'content',
                $instance->revision,
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
            resource_view($instance, $course, $cm, $context);
        }
        return $result;
    }

    /**
     * Embed address of a YouTube or Vimeo link.
     *
     * @param string $url
     * @return string '' when the link is not a video of those sites
     */
    public static function video_embed_url(string $url): string {
        $patterns = [
            '~^https?://(?:www\.|m\.)?youtube\.com/watch\?(?:.*&)?v=([\w-]{11})~i' => 'https://www.youtube-nocookie.com/embed/%s',
            '~^https?://(?:www\.)?youtube\.com/(?:shorts|embed|live)/([\w-]{11})~i' => 'https://www.youtube-nocookie.com/embed/%s',
            '~^https?://youtu\.be/([\w-]{11})~i' => 'https://www.youtube-nocookie.com/embed/%s',
            '~^https?://(?:www\.)?vimeo\.com/(\d+)~i' => 'https://player.vimeo.com/video/%s',
        ];
        foreach ($patterns as $pattern => $embed) {
            if (preg_match($pattern, trim($url), $matches)) {
                return sprintf($embed, $matches[1]);
            }
        }
        return '';
    }

    /**
     * Result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'type' => new external_value(PARAM_ALPHA, 'html, embed, pdf or none (open the activity as usual)'),
            'title' => new external_value(PARAM_TEXT, 'Name of the activity'),
            'html' => new external_value(PARAM_RAW, 'Content, for html'),
            'url' => new external_value(PARAM_URL, 'Address of the video or the file, for embed and pdf'),
            'viewurl' => new external_value(PARAM_URL, 'Page of the activity'),
        ]);
    }
}
