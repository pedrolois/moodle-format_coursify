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

/**
 * Tests of the web service that gives the content of an activity for the window over the course page.
 *
 * @package   format_coursify
 * @category  test
 * @copyright 2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_coursify\external\get_activity_content::class)]
final class get_activity_content_test extends \advanced_testcase {
    /**
     * Call the web service as it is called from the browser.
     *
     * @param int $cmid
     * @return array
     */
    protected function call(int $cmid): array {
        $result = get_activity_content::execute($cmid);
        return external_api::clean_returnvalue(get_activity_content::execute_returns(), $result);
    }

    /**
     * A page comes as HTML, and opening it counts as viewing it.
     */
    public function test_page(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify', 'enablecompletion' => 1]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id,
            'content' => '<p>Welcome to the course</p>', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $sink = $this->redirectEvents();
        $result = $this->call($page->cmid);
        $this->assertSame(get_activity_content::TYPE_HTML, $result['type']);
        $this->assertStringContainsString('Welcome to the course', $result['html']);
        $this->assertStringContainsString('/mod/page/view.php', $result['viewurl']);
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof \mod_page\event\course_module_viewed);
        $this->assertCount(1, $events);
        $sink->close();
        $this->assertEquals(COMPLETION_COMPLETE, $DB->get_field(
            'course_modules_completion',
            'completionstate',
            ['coursemoduleid' => $page->cmid, 'userid' => $student->id]
        ));
    }

    /**
     * Video links come as an embed address; other links open as usual.
     */
    public function test_url(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $video = $this->getDataGenerator()->create_module('url', ['course' => $course->id,
            'externalurl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $site = $this->getDataGenerator()->create_module('url', ['course' => $course->id,
            'externalurl' => 'https://moodle.org']);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $result = $this->call($video->cmid);
        $this->assertSame(get_activity_content::TYPE_EMBED, $result['type']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $result['url']);
        $this->assertSame(get_activity_content::TYPE_NONE, $this->call($site->cmid)['type']);
    }

    /**
     * Other activities and other course formats open as usual.
     */
    public function test_none(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $topics = $this->getDataGenerator()->create_course(['format' => 'topics']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $topics->id]);
        $this->setAdminUser();
        $this->assertSame(get_activity_content::TYPE_NONE, $this->call($forum->cmid)['type']);
        $this->assertSame(get_activity_content::TYPE_NONE, $this->call($page->cmid)['type']);
    }

    /**
     * Users who cannot enter the course get nothing.
     */
    public function test_no_access(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\require_login_exception::class);
        $this->call($page->cmid);
    }

    /**
     * Hidden activities are not given to students.
     */
    public function test_hidden(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'coursify']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->expectException(\moodle_exception::class);
        $this->call($page->cmid);
    }

    /**
     * Embed addresses of video links.
     *
     * @param string $url
     * @param string $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('video_provider')]
    public function test_video_embed_url(string $url, string $expected): void {
        $this->assertSame($expected, get_activity_content::video_embed_url($url));
    }

    /**
     * Video links.
     *
     * @return array
     */
    public static function video_provider(): array {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            ['https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            ['https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871'],
            ['https://moodle.org/', ''],
            ['https://evil.example/?https://youtu.be/dQw4w9WgXcQ', ''],
        ];
    }
}
