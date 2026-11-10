<p align="center"><img src="pix/icon.png" alt="Coursify" width="160"></p>

# Coursify — Moodle Course Format

**Create. Organize. Learn.**

Coursify shows a course as tiles, a learning path, a list or a map, one stop per section. Each one opens its section on its own page.

## Features

- **Four layouts:**
  - **Grid** of cards, or of large photos with the title over them.
  - **Learning path:** numbered steps along a line, showing completed steps, the next one and locked ones.
  - **List:** one compact row per section.
  - **Map:** the sections are stops along a trail, with the learner's picture on the next stop. Nine map themes:
    road, river, treasure map, board game, space mission (planets), mountain trek (small mountains joined by a rope),
    chess board (a chess piece at each stop, up to the king), parchís (tokens between the four coloured homes) and
    minimal. The stops can be round, square or triangular in the themes without stops of their own shape.
- **Live preview in the course settings.** A column next to the Coursify settings shows the course page as learners will see it, with the progress of a sample learner half way through the course. It updates on every change, highlights the part that changed and shows which setting it was. Settings that do nothing with the chosen layout are hidden.
- **Tiles** with an image, a colour, an icon or their number, in the numbering you choose (1, 2, 3; A, B, C; or I, II, III).
- **Colour palettes** (rainbow, ocean, sunset, forest, calm). They colour every section in turn, so the whole course matches in one click.
- **Groups of sections:** a section with a group name, such as "Unit 1", starts a new group with its own heading and progress.
- **Section dates:** weekly dates from the course start, with the duration of each section. The section of this week is highlighted and future sections show when they open.
- **Course banner** with the course image, summary, teachers, progress and the Continue button.
- **Progress:**
  - each tile shows how much of the section is done, as a ring, a bar or the percentage;
  - a bar shows the progress of the whole course;
  - a check mark appears on completed tiles;
  - filters show all sections, the ones to do or the ones done.
- **Continue** button that opens the next activity the learner has not completed yet, or the last section visited.
- **Activities in a window:** pages, PDF files and YouTube or Vimeo links open over the course page, and their completion is updated at once.
- **Locked tiles** that explain why they are locked, for example "Not available unless: you complete Getting started".
- **Section pages** with a strip of every section and links to the previous tile, the next tile and the whole course.
- **Standard Moodle course editor in edit mode:** drag and drop, bulk editing, subsections and the course index all work as usual.
- **Import from the Tiles format.** Switching a course from Tiles to Coursify keeps its tile photos, icons and base colour. To switch many courses at once, run `php course/format/coursify/cli/import_tiles.php --all`.
- **Theme colours,** so dark mode and branded themes work.
- **Backup and restore,** tile images included.
- **Moodle app support.** The app shows the tiles with their images, colours, groups, dates and progress, plus Continue and the course progress. Each section opens with its activities and previous/next buttons.
- **Privacy API:** the last section visited is a user preference, exported and deleted with the user's data.
- **Supported languages:** English and Spanish.

## Requirements

Moodle 5.3.

## Installation

Copy the folder to `course/format/coursify`, then visit *Site administration > Notifications*.

## Settings

- **Site defaults:** *Site administration > Plugins > Course formats > Coursify*.
- **Per course:** *Course settings > Course format*, with the live preview next to them. The options are:
  - layout, colour palette and numbering;
  - tiles per row and tile style (grid);
  - map theme and shape of the stops (map);
  - course banner and the position of its image;
  - progress and its style (ring, bar or percentage), filters and "Continue";
  - activities in a window;
  - section dates;
  - course index.
- **Per section:** *Edit section > Tile*. The options are:
  - colour, icon and image;
  - group name;
  - duration, for section dates.

## Tests

- PHPUnit and Behat tests run with the Moodle test environments, and in GitHub Actions with moodle-plugin-ci.
- End-to-end tests with Playwright are in `tests/playwright` (see its README).

## Author

Pedro Luis García Leiva.

## Licence

GNU GPL v3 or later.
