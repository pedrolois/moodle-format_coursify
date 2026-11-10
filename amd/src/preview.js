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
 * Live preview of the course page next to the Coursify settings of the course edit form.
 *
 * Each change of a setting renders the course again with the chosen values (core fragment API) and shows it
 * in a column on the right of the settings.
 *
 * @module     format_coursify/preview
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fragment from 'core/fragment';
import Templates from 'core/templates';
import {getString} from 'core/str';

/** Settings of the course that change how the course page looks. */
const OPTIONS = ['layout', 'tilepalette', 'tilecolumns', 'tilestyle', 'maptheme', 'mapshape', 'numbering', 'showbanner',
    'bannerposition', 'showprogress', 'progressstyle', 'showcontinue', 'showfilters', 'sectiondates'];

/** Wait after the last change before rendering, so quick changes render once. */
const DELAY = 250;

/** Tiles of every layout. */
const TILES = '.coursify-grid, .coursify-path, .coursify-list, .coursify-map';

/** Where each setting shows in the course page, to point at it after a change. */
const TARGETS = {
    layout: TILES,
    tilepalette: TILES,
    tilecolumns: '.coursify-grid',
    tilestyle: '.coursify-grid',
    maptheme: '.coursify-map',
    mapshape: '.coursify-map__node',
    numbering: '.coursify-tile__number, .coursify-step__label, .coursify-map__label',
    showbanner: '.coursify-banner',
    bannerposition: '.coursify-banner',
    showprogress: '.coursify-summary__progress, .coursify-ring__value, .coursify-map__ring',
    progressstyle: '.coursify-ring, .coursify-pbar, .coursify-ppct, .coursify-step__ring, .coursify-map__ring',
    showcontinue: '.coursify-continue',
    showfilters: '.coursify-filters',
    sectiondates: '.coursify-tile__dates, .coursify-tile__badges .badge',
};

/** At most this many elements are highlighted, so a change in every tile does not light up the whole page. */
const MAXHIGHLIGHT = 6;

/**
 * Values of the settings in the form.
 *
 * @param {HTMLFormElement} form
 * @returns {Object}
 */
const values = (form) => {
    const result = {};
    OPTIONS.forEach((name) => {
        const field = form.elements.namedItem(name);
        if (field) {
            result[name] = field.value;
        }
    });
    return result;
};

/**
 * Builds the two columns: the settings of the format on the left, the preview on the right.
 *
 * @param {HTMLElement} fieldset the "Course format" section of the form
 * @returns {Promise<Object>} the elements of the preview
 */
const build = async(fieldset) => {
    const container = fieldset.querySelector('.fcontainer') || fieldset;
    const [title, loading, hint] = await Promise.all([
        getString('preview', 'format_coursify'),
        getString('previewloading', 'format_coursify'),
        getString('previewhint', 'format_coursify'),
    ]);

    const settings = document.createElement('div');
    settings.className = 'coursify-preview-settings';
    while (container.firstChild) {
        settings.appendChild(container.firstChild);
    }

    const column = document.createElement('div');
    column.className = 'coursify-preview-column';
    column.innerHTML = `
        <div class="coursify-preview">
            <div class="coursify-preview__bar">
                <span class="coursify-preview__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                <strong class="coursify-preview__title"></strong>
                <span class="coursify-preview__change" hidden></span>
                <span class="coursify-preview__status" role="status"></span>
            </div>
            <div class="coursify-preview__viewport" inert>
                <div class="coursify-preview__canvas"></div>
            </div>
            <p class="coursify-preview__hint small text-muted mb-0"></p>
        </div>`;
    column.querySelector('.coursify-preview__title').textContent = title;
    column.querySelector('.coursify-preview__hint').textContent = hint;

    const layout = document.createElement('div');
    layout.className = 'coursify-preview-layout';
    layout.append(settings, column);
    container.appendChild(layout);

    return {
        preview: column.querySelector('.coursify-preview'),
        canvas: column.querySelector('.coursify-preview__canvas'),
        status: column.querySelector('.coursify-preview__status'),
        change: column.querySelector('.coursify-preview__change'),
        viewport: column.querySelector('.coursify-preview__viewport'),
        loading,
    };
};

/**
 * Label and chosen value of a setting, as the form shows them.
 *
 * @param {HTMLFormElement} form
 * @param {String} name
 * @returns {String}
 */
const describe = (form, name) => {
    const field = form.elements.namedItem(name);
    const label = document.getElementById(`id_${name}_label`)?.textContent.trim() || name;
    const value = field?.selectedOptions?.[0]?.textContent.trim() ?? field?.value ?? '';
    return `${label}: ${value}`;
};

/**
 * Points at the part of the preview that the last change affects: it flashes and is scrolled into view.
 *
 * @param {Object} ui
 * @param {String} name setting that changed
 */
const highlight = (ui, name) => {
    const elements = Array.from(ui.canvas.querySelectorAll(TARGETS[name] || TILES)).slice(0, MAXHIGHLIGHT);
    elements.forEach((element) => {
        element.classList.remove('coursify-preview-flash');
        // Restart the animation when the same element changes again.
        void element.offsetWidth;
        element.classList.add('coursify-preview-flash');
    });
    if (elements.length) {
        const viewport = ui.viewport.getBoundingClientRect();
        const target = elements[0].getBoundingClientRect();
        if (target.top < viewport.top || target.bottom > viewport.bottom) {
            ui.viewport.scrollBy({top: target.top - viewport.top - 24, behavior: 'smooth'});
        }
    }
};

/**
 * Starts the preview.
 *
 * @param {Number} contextid context of the course
 */
export const init = async(contextid) => {
    const layoutfield = document.getElementById('id_layout');
    const form = layoutfield?.form;
    const fieldset = layoutfield?.closest('fieldset');
    if (!form || !fieldset || fieldset.dataset.coursifyPreview) {
        return;
    }
    fieldset.dataset.coursifyPreview = '1';
    const ui = await build(fieldset);

    let timer = null;
    let request = 0;
    let changed = null;
    const render = async() => {
        const current = ++request;
        ui.preview.classList.add('coursify-preview--loading');
        ui.status.textContent = ui.loading;
        try {
            // The fragment promise (jQuery) resolves with two arguments: the HTML and its JavaScript.
            const [html, js] = await new Promise((resolve, reject) => {
                Fragment.loadFragment('format_coursify', 'preview', contextid, {options: JSON.stringify(values(form))})
                    .then((fragmenthtml, fragmentjs) => resolve([fragmenthtml, fragmentjs]))
                    .catch(reject);
            });
            if (current !== request) {
                // A newer change is already being rendered.
                return;
            }
            await Templates.replaceNodeContents(ui.canvas, html, js);
            if (changed) {
                highlight(ui, changed);
            }
        } catch (error) {
            if (current === request) {
                ui.canvas.textContent = error.message || String(error);
            }
        } finally {
            if (current === request) {
                ui.preview.classList.remove('coursify-preview--loading');
                ui.status.textContent = '';
            }
        }
    };

    form.addEventListener('change', (event) => {
        if (OPTIONS.includes(event.target.name)) {
            changed = event.target.name;
            ui.change.textContent = describe(form, changed);
            ui.change.hidden = false;
            ui.change.classList.remove('coursify-preview-flash');
            void ui.change.offsetWidth;
            ui.change.classList.add('coursify-preview-flash');
            clearTimeout(timer);
            timer = setTimeout(render, DELAY);
        }
    });
    render();
};
