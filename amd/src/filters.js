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
 * Filters of the tiles: all, to do, done.
 *
 * @module     format_coursify/filters
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    FILTERS: '[data-region="coursify-filters"]',
    TILES: '[data-region="coursify-tiles"]',
    TILE: '[data-region="coursify-tile"]',
    GROUP: '[data-region="coursify-group"]',
    NOMATCH: '[data-region="coursify-nomatch"]',
};

/**
 * Whether a tile matches a filter.
 *
 * @param {HTMLElement} tile
 * @param {string} filter all, todo or done
 * @returns {boolean}
 */
const matches = (tile, filter) => filter === 'all' || tile.dataset.status === filter;

/**
 * Show only the tiles that match the filter; hide the groups left empty.
 *
 * @param {HTMLElement} container
 * @param {string} filter
 */
const apply = (container, filter) => {
    let shown = 0;
    container.querySelectorAll(SELECTORS.TILE).forEach(tile => {
        tile.hidden = !matches(tile, filter);
        shown += tile.hidden ? 0 : 1;
    });
    // Grid and list: a group is a block with its tiles inside.
    container.querySelectorAll(`section${SELECTORS.GROUP}`).forEach(group => {
        group.hidden = !group.querySelector(`${SELECTORS.TILE}:not([hidden])`);
    });
    // Path: a group is a milestone followed by its steps.
    container.querySelectorAll(`li${SELECTORS.GROUP}`).forEach(milestone => {
        let next = milestone.nextElementSibling;
        let visible = false;
        while (next && !next.matches(SELECTORS.GROUP)) {
            visible = visible || !next.hidden;
            next = next.nextElementSibling;
        }
        milestone.hidden = !visible;
    });
    const nomatch = container.querySelector(SELECTORS.NOMATCH);
    if (nomatch) {
        nomatch.hidden = shown > 0;
    }
};

/**
 * Set up the filter buttons.
 */
export const init = () => {
    const filters = document.querySelector(SELECTORS.FILTERS);
    const container = document.querySelector(SELECTORS.TILES);
    if (!filters || !container || filters.dataset.initialised) {
        return;
    }
    filters.dataset.initialised = 1;
    filters.addEventListener('click', event => {
        const button = event.target.closest('[data-filter]');
        if (!button) {
            return;
        }
        filters.querySelectorAll('[data-filter]').forEach(other => {
            other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
        });
        apply(container, button.dataset.filter);
    });
};
