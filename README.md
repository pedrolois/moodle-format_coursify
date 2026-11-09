# Formatium — tiles course format for Moodle

Formatium shows a course as a grid of tiles, one per section. Each tile opens its section on its own page.

## Features

- **Tiles** with an image, an accent colour from the theme, or an icon. Tiles without any of these show their number.
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

Copy the folder to `course/format/formatium`, then visit *Site administration > Notifications*.

## Settings

- **Site defaults:** *Site administration > Plugins > Course formats > Formatium*.
- **Per course:** *Course settings > Course format*. The options are:
  - tiles per row;
  - show progress;
  - show "Continue".
- **Per section:** *Edit section > Tile*. The options are:
  - colour;
  - icon;
  - image.

## Licence

GNU GPL v3 or later.
