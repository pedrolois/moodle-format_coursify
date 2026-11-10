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
 * Map layout: places the stops in a winding order (left to right, then right to left) for the width available and
 * draws the trail through them, the part already done in its own colour.
 *
 * @module     format_coursify/map
 * @copyright  2026 Pedro Lois {@link https://github.com/pedrolois}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    MAP: '[data-region="coursify-map"]',
    STOP: '[data-region="coursify-tile"]',
    NODE: '[data-region="coursify-map-node"]',
    FINISH: '.coursify-map__finish',
};

/**
 * Number of stops per row for a width.
 *
 * @param {number} width
 * @returns {number}
 */
const columnsFor = width => {
    if (width >= 720) {
        return 4;
    }
    if (width >= 520) {
        return 3;
    }
    return width >= 360 ? 2 : 1;
};

/**
 * Put each stop in its cell: odd rows go right to left.
 *
 * @param {HTMLElement} map
 * @param {HTMLElement[]} stops
 * @param {number} columns
 */
const placeStops = (map, stops, columns) => {
    map.style.setProperty('--coursify-map-columns', columns);
    stops.forEach((stop, index) => {
        const row = Math.floor(index / columns);
        const position = index % columns;
        const column = row % 2 ? columns - 1 - position : position;
        stop.style.gridRow = row + 1;
        stop.style.gridColumn = column + 1;
    });
};

/**
 * SVG path through some points: straight within a row, a round bend at the end of each row.
 *
 * @param {Array<{x: number, y: number}>} points
 * @param {number} bend how far the bends go out
 * @param {number} width width of the map
 * @returns {string}
 */
const pathThrough = (points, bend, width) => {
    if (!points.length) {
        return '';
    }
    let d = `M ${points[0].x} ${points[0].y}`;
    let rowchanges = 0;
    for (let i = 1; i < points.length; i++) {
        const from = points[i - 1];
        const to = points[i];
        if (Math.abs(from.y - to.y) < 4) {
            d += ` L ${to.x} ${to.y}`;
        } else {
            // Change of row: go out to the side where the row ends (one column: alternate sides) and come back.
            const middle = width / 2;
            let side = from.x > middle + 4 ? 1 : -1;
            if (Math.abs(from.x - middle) <= 4) {
                side = rowchanges % 2 ? -1 : 1;
            }
            rowchanges++;
            const out = side * bend;
            d += ` C ${from.x + out} ${from.y}, ${to.x + out} ${to.y}, ${to.x} ${to.y}`;
        }
    }
    return d;
};

/**
 * Draw the trail of a map.
 *
 * @param {HTMLElement} map
 */
const draw = map => {
    const stops = [...map.querySelectorAll(SELECTORS.STOP)].filter(stop => !stop.hidden);
    if (!stops.length) {
        return;
    }
    const width = map.clientWidth;
    // Some themes size their background with the width of the map, such as the squares of the chess board.
    map.style.setProperty('--coursify-map-width', `${width}px`);
    placeStops(map, stops, columnsFor(width));
    const box = map.getBoundingClientRect();
    // Screen sizes over CSS sizes: below 1 when the map is drawn smaller, as in the preview of the settings.
    const scale = box.width && map.offsetWidth ? box.width / map.offsetWidth : 1;
    const points = stops.map(stop => {
        const node = stop.querySelector(SELECTORS.NODE).getBoundingClientRect();
        return {
            x: (node.left - box.left + node.width / 2) / scale,
            y: (node.top - box.top + node.height / 2) / scale,
        };
    });
    // The trail goes on to the finish flag, after the last stop.
    const finish = map.querySelector(SELECTORS.FINISH);
    const last = points[points.length - 1];
    const lastrow = Math.floor((stops.length - 1) / columnsFor(width));
    const direction = lastrow % 2 ? -1 : 1;
    const end = {x: Math.min(Math.max(last.x + direction * 70, 24), width - 24), y: last.y};
    if (finish) {
        finish.style.left = `${end.x}px`;
        finish.style.top = `${end.y}px`;
    }
    const all = [...points, end];
    const bend = Math.min(70, width / 8);
    // The part done goes up to the stop with the next activity, or to the last stop completed.
    let doneupto = stops.findIndex(stop => stop.classList.contains('coursify-tile--next'));
    if (doneupto < 0) {
        doneupto = stops.reduce((found, stop, index) => (stop.dataset.status === 'done' ? index : found), -1);
    }
    const svg = map.querySelector('svg');
    svg.setAttribute('viewBox', `0 0 ${width} ${map.clientHeight}`);
    svg.setAttribute('width', width);
    svg.setAttribute('height', map.clientHeight);
    map.querySelectorAll('[data-path="all"]').forEach(path => path.setAttribute('d', pathThrough(all, bend, width)));
    map.querySelectorAll('[data-path="done"]').forEach(path => {
        path.setAttribute('d', doneupto > 0 ? pathThrough(all.slice(0, doneupto + 1), bend, width) : '');
    });
};

/**
 * Set up the maps of the page.
 */
export const init = () => {
    document.querySelectorAll(SELECTORS.MAP).forEach(map => {
        if (map.dataset.initialised) {
            return;
        }
        map.dataset.initialised = 1;
        let frame = null;
        const redraw = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => draw(map));
        };
        new ResizeObserver(redraw).observe(map);
        // The filters show and hide stops.
        new MutationObserver(redraw).observe(map, {attributes: true, attributeFilter: ['hidden'], subtree: true});
        map.querySelectorAll('img').forEach(img => img.addEventListener('load', redraw));
        redraw();
    });
};
