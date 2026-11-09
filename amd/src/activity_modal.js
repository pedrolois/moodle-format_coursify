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
 * Pages, PDF files and video links open in a window over the course page; closing it refreshes the completion.
 *
 * @module     format_coursify/activity_modal
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Config from 'core/config';
import Fragment from 'core/fragment';
import Modal from 'core/modal';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

const SELECTORS = {
    ACTIVITY: '[data-for="cmitem"]',
    LINK: 'a[href*="/mod/page/view.php"], a[href*="/mod/url/view.php"], a[href*="/mod/resource/view.php"]',
};

/**
 * Open an activity in a window, or follow its link when it cannot be shown in one.
 *
 * @param {number} cmid
 * @param {string} href
 */
const open = async(cmid, href) => {
    let content;
    try {
        content = await Ajax.call([{methodname: 'format_coursify_get_activity_content', args: {cmid}}])[0];
    } catch (error) {
        window.location.href = href;
        return;
    }
    if (content.type === 'none') {
        window.location.href = href;
        return;
    }
    const body = await Templates.render('format_coursify/activity_modal', {
        ishtml: content.type === 'html',
        isembed: content.type === 'embed',
        ispdf: content.type === 'pdf',
        html: content.html,
        url: content.url,
        viewurl: content.viewurl,
        title: content.title,
    });
    const modal = await Modal.create({title: content.title, body, large: true, show: true, removeOnClose: true});
    modal.getRoot().addClass('format-coursify-modal');
    modal.getRoot().on('modal:hidden', () => {
        refresh(cmid);
    });
};

/**
 * Viewing can complete the activity: show its new state on the page and in the course index.
 *
 * @param {number} cmid
 */
const refresh = async(cmid) => {
    const editor = getCurrentCourseEditor();
    if (editor) {
        editor.dispatch('cmState', [cmid]);
    }
    const cmitem = document.querySelector(`${SELECTORS.ACTIVITY}[data-id="${cmid}"]`);
    if (!cmitem) {
        return;
    }
    const {html, js} = await Fragment.loadFragment('core_courseformat', 'cmitem', Config.courseContextId, {
        id: cmid,
        courseid: Config.courseId,
        sr: editor?.sectionReturn ?? null,
        pagesectionid: editor?.pageSectionId ?? null,
    }).then((html, js) => ({html, js}));
    if (document.contains(cmitem)) {
        Templates.replaceNode(cmitem, html, js);
    }
};

/**
 * Catch the clicks on pages, files and links of the course page.
 */
export const init = () => {
    if (document.body.dataset.coursifyModal) {
        return;
    }
    document.body.dataset.coursifyModal = 1;
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }
        if (document.body.classList.contains('editing')) {
            return;
        }
        const link = event.target.closest(SELECTORS.LINK);
        const activity = link ? link.closest(SELECTORS.ACTIVITY) : null;
        if (!activity || !activity.dataset.id) {
            return;
        }
        event.preventDefault();
        open(parseInt(activity.dataset.id), link.href).catch(Notification.exception);
    });
};
