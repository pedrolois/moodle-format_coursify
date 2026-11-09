# Coursify — Moodle Course Format

**Create. Organize. Learn.**

Coursify shows a course as tiles, a learning path, a list or a map, one stop per section. Each one opens its section on its own page.

## Features

- **Three layouts:**
  - **Grid** of cards.
  - **Learning path:** numbered steps along a line, showing completed steps, the next one and locked ones.
  - **List:** one compact row per section.
- **Tiles** with an image, an accent colour from the theme, or an icon. Tiles without any of these show their number.
- **Colour palettes** (rainbow, ocean, sunset, forest, calm). They colour every section without its own colour, so a course looks good in one click.
- **"Up next" badge** on the section that holds the next activity to do.
- **Import from the Tiles format.** Switching a course from Tiles to Coursify keeps its tile photos, icons and base colour. To switch many courses at once, run `php course/format/coursify/cli/import_tiles.php --all`.
- **Progress:**
  - each tile has a ring showing how much of the section is done;
  - a bar above the tiles shows the progress of the whole course;
  - a check mark appears on completed tiles.
- **Continue** button that opens the next activity the learner has not completed yet.
- **Locked tiles** that explain why they are locked, for example "Not available unless: you complete Getting started".
- **Section pages** with links to the previous tile, the next tile and the whole course.
- **Standard Moodle course editor in edit mode:** drag and drop, bulk editing, subsections and the course index all work as usual.
- **Theme colours,** so dark mode and branded themes work.
- **Backup and restore,** tile images included.
- **Supported languages:** English and Spanish.

## Requirements

Moodle 5.3.

## Installation

Copy the folder to `course/format/coursify`, then visit *Site administration > Notifications*.

## Settings

- **Site defaults:** *Site administration > Plugins > Course formats > Coursify*.
- **Per course:** *Course settings > Course format*. The options are:
  - layout;
  - colour palette;
  - tiles per row;
  - show progress;
  - show "Continue".
- **Per section:** *Edit section > Tile*. The options are:
  - colour;
  - icon;
  - image.

## Licence

GNU GPL v3 or later.
