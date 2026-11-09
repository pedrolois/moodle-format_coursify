# Coursify roadmap

This roadmap comes from a study of the six most installed course formats on Moodle 5.3:

| Format | Supports 5.3 | Notable problems on 5.3 |
|---|---|---|
| Tiles 5.1 | No | Overrides the `.main-inner` width, so the 5.3 anchored drawers float over the tiles. `core/modal_factory` has been removed, so its modals fail. Uses jQuery. |
| Onetopic 5.1 | No | `core/modal_factory` has been removed, so its JS fails. Teachers' raw CSS is injected unescaped. Uses Bootstrap 4 CSS variables, so dark mode breaks. |
| Edwiser (remuiformat) | No (up to 5.2) | Web services without capability checks. Sends telemetry to edwiser.org. Shows ads. Has no backup of its images. |
| Flexible sections | Yes | Uses its own nesting model, incompatible with core subsections. Deep nesting does not fit the 870px column. User preferences are missing from its privacy provider. |
| Buttons | Yes | Buttons are labelled only with "1", "b" or "IV". Section access restrictions are ignored. Colours are inline. Some classes are in the wrong namespace. |
| Multitopic | Yes | Copies large parts of core code. Colours are hardcoded. Its tabs wrap badly inside the 870px column. |

**None of them supports the Moodle app.**

## What Coursify takes from each, improved

| Idea | From | Coursify version | Status |
|---|---|---|---|
| Tiles with photo, icon and colour | Tiles | Theme colours, palettes, image or icon | Done |
| Progress per tile and overall | Tiles, Edwiser | Rings and bar. Text changes with progress: Start, x of y, Done | Done |
| Continue / resume | Edwiser, Buttons, Onetopic | Goes to the next activity not yet completed. Without completion tracking, goes back to the last section visited (stored as a user preference) | Partly done (resume pending) |
| Locked sections with the reason | Onetopic | The reason is shown on the tile | Done |
| Grid, path, list layouts | Tiles, Edwiser | Three layouts and palettes | Done |
| Course hero banner | Edwiser, Multitopic | Course image with focal point and a scrim. Shows course contacts, progress and Continue. Stays inside the 5.3 column | Next |
| Section groups / chapters | Buttons, Onetopic, Multitopic | A section option starts a group with a heading in every layout, plus filter chips | Next |
| Section dates | Multitopic | Weekly chain from the course start, with a per-section duration. Shows a date chip and "This week". Locked tiles show "Opens on" | Next |
| Section switcher on section pages | Buttons, Onetopic | A sticky strip of mini tiles (aria-current, scrolls horizontally) | Next |
| Numbering style | Buttons | Numbers, letters, Roman numerals | Next |
| Course index mode, General header | Flexible sections | Full, sections only, or hidden. General header can be hidden | Next |
| Filter chips | Tiles | All / To do / Done, and by group | Next |
| Activities in a modal (page, PDF, video) | Tiles | `core/modal` and a web service that logs the view; closing it reloads the activity, so completion shows straight away | Done |
| Live progress after manual completion | Tiles | The activity and the course index update without a reload | Done |
| Back to section on activity pages | Flexible sections, Multitopic, Onetopic | Not needed: the 5.3 breadcrumbs link the section, and core already has activity navigation | Dropped |
| Subsection display (cards / list) | Onetopic | Subsections shown as mini tiles | Later |
| Inline activity links in summaries | Onetopic | Linked by activity id, with completion state | Maybe |
| Style tokens editor | Onetopic | Validated tokens turned into CSS variables. No raw CSS | Maybe |
| Moodle app support | (none) | `db/mobile.php`: tiles, groups, dates, progress, Continue, section pages | Done |
