# Notes: Obsidian, Apple Notes and Box Notes parity task list

What Obsidian, Apple Notes (macOS / iOS 18–26) and Box Notes have that Notes doesn't, as of branch `claude/bear-ladder` (Bear ladder 0.1–6.2 done).
Each task: one commit, server logic + policy/scoping + validation + Vue UI + Pest tests, same rules as the Bear ladder.
⚠️ dep = needs a new npm/composer dependency, so it needs your approval first. 🧩 = large; I'd split it into sub-commits.
O = Obsidian feature, A = Apple Notes-only feature, B = Box Notes-only feature.
🍎 = Apple Notes has it too, 📦 = Box Notes has it too (each task is built once).

Already have (not repeated below): notes CRUD, title from first line, autosave, pin, Markdown preview
(edit/split/preview), task-list checkboxes, B/I/K toolbar, nested #tags + tag tree, full-text search + highlight,
archive, trash + 30-day purge (Apple's Recently Deleted), [[wiki links]] + backlinks.

The original pre-0.1 Notes mock-up already sketched several Apple Notes features (text style menu, coloured highlights,
Group by Date, Lock note, Share, tables, attachments); they're covered by the tasks below.

Still open from the Bear ladder (also Obsidian features): export .md/.zip, import .md, word count/info panel,
duplicate note, sort options, version history, global shortcuts. They're folded into the tasks below.

## O1. Organizing notes
- [x] O1.1 Folders: nested folders table, note belongs to one folder, file-explorer tree in sidebar, counts 🍎 📦
- [x] O1.2 Create / rename / move / delete folders (delete moves notes to trash), drag-and-drop notes into folders; restoring a trashed note puts it back in its original folder 🍎
- [x] O1.3 Sort options for list and explorer: name, created, modified (asc/desc), remembered per user (Bear 7.4) (Apple: sort per folder) 🍎
- [x] O1.4 Bookmarks: bookmark notes, headings, blocks and saved searches; bookmark groups; Bookmarks panel (Box: favorites / collections) 📦
- [x] O1.5 Rename note from the editor header (title stays derived from line 1; rename rewrites line 1)
- [x] O1.6 Duplicate note (Bear 7.4)

## O2. Links and graph
- [ ] O2.1 Link aliases: `[[Title|shown text]]`
- [ ] O2.2 Heading links: `[[Title#Heading]]` scroll to the heading; `[[#Heading]]` within the same note
- [ ] O2.4 Link autocomplete: typing `[[` suggests note titles (fuzzy), then headings after `#` (Apple: "Add Link" on a selection picks a note) 🍎
- [ ] O2.5 Tag autocomplete when typing `#`
- [ ] O2.6 Update links on rename: when a note's title changes, rewrite `[[Old]]` → `[[New]]` in other notes (with confirm) 🍎
- [ ] O2.7 Backlinks with context: show the surrounding line/paragraph for each backlink, not just the title
- [ ] O2.8 Unlinked mentions: notes that contain the title as plain text, with a one-click "link" action
- [ ] O2.9 Outgoing links panel: links from this note, resolved and unresolved
- [ ] O2.10 Hover preview: hovering a wiki link shows a popover with the target note's rendered content

## O3. Embeds and attachments
- [ ] O3.1 Media upload: images, videos, audio and other files (paste, drag-and-drop, button), stored per user, served through a policy-checked route 🧩 (Box: embed files from storage with inline preview) 🍎 📦
  - [ ] O3.1a Upload rules: allowed types per kind (image / video / audio / document), size limit per kind, MIME sniffed on the server (not trusted from the browser), dangerous types (SVG, HTML, executables) blocked or served as downloads only
  - [ ] O3.1c Image processing: thumbnails and responsive sizes, compression, EXIF/GPS metadata stripped, orientation fixed (queued job)
  - [ ] O3.1d Video processing: poster frame, duration and dimensions, HTTP range requests so playback can seek (queued job) ⚠️ dep (FFmpeg) for poster frames
  - [ ] O3.1e Mobile camera capture: take a photo or record a video straight into the note (`capture` on file inputs)
  - [ ] O3.1f In-browser video recording (camera / screen) into the note, alongside the voice memo in O3.6
  - [ ] O3.1g Upload progress bar and cancel
- [ ] O3.2 Image embeds `![[image.png]]` with size syntax `![[image.png|300]]` (Apple: small/large size toggle, full-screen viewer) 🍎 📦
- [ ] O3.3 Note transclusion `![[Other note]]`, `![[Note#Heading]]` (rendered inline, loop-safe)
- [ ] O3.4 PDF, audio and video embeds with inline players/viewer 🍎 📦
- [ ] O3.5 Attachment manager: list attachments, show which notes use each one, delete unused ones (Apple: grouped as photos & videos, scans, maps, websites, audio, documents) 🍎
- [ ] O3.6 Audio recorder: record a voice memo into the current note 🍎

## O4. Editor
- [x] O4.0 Tiptap editor: WYSIWYG editing that loads and saves Markdown (`@tiptap/markdown`), custom nodes for [[wiki links]], #tags, checklists and ==highlights==, a Source toggle for raw Markdown, round-trip tests ⚠️ dep
- [x] O4.3 Reading view as its own mode (no editor chrome), plus "readable line length" toggle
- [x] O4.4 Smart lists: Enter continues bullets/numbers/checkboxes; Tab / Shift+Tab indent/outdent; empty item ends the list 🍎
- [x] O4.5 Auto-pair brackets, quotes and Markdown markers (`**`, `` ` ``, `[[`)
- [x] O4.6 Paste URL over a selection creates `[selection](url)`; paste rich HTML converts to Markdown 🍎
- [x] O4.7 Folding: collapse headings and nested list items (editor + preview) 🍎
- [x] O4.8 Move line up/down, duplicate line, toggle comment shortcuts
- [x] O4.11 Slash commands: `/` menu to insert headings, lists, tables, callouts, embeds, templates (Box: "+" insert menu) 📦
- [x] O4.12 Table editor: insert table, add/remove rows/cols, Tab between cells, auto-align (Box: merge cells, cell background colours, resizable columns, header row) 🍎 📦
- [x] O4.13 Spellcheck toggle and right-to-left text support (settings)
- [x] O4.14 Find and replace inside the current note (regex, case-sensitive) 🍎
- [x] O4.15 Word / character count + reading time in status bar / info panel (Bear 7.3) 🍎

## O5. Markdown extensions (rendering)
- [ ] O5.1 Callouts: `> [!note]`, `[!warning]`, foldable `[!tip]-`, custom titles
- [ ] O5.2 Footnotes: `[^1]` references and definitions; footnotes view
- [ ] O5.3 Comments: `%%hidden%%` stripped from preview
- [ ] O5.4 Math: inline `$…$` and block `$$…$$` (LaTeX) ⚠️ dep (KaTeX/MathJax)
- [ ] O5.5 Mermaid diagrams in ```mermaid blocks ⚠️ dep (mermaid)
- [ ] O5.6 Heading anchors / IDs in preview, plus a table of contents 📦
- [ ] O5.7 Strict vs soft line-break setting
- [ ] O5.8 Safe-subset HTML: allow a vetted allow-list (e.g. `<details>`, `<kbd>`, `<sup>`) while still blocking scripts

## O6. Properties (front matter)
- [x] O6.1 YAML front matter parsed and hidden from preview/title/snippet
- [x] O6.2 Properties panel: typed fields (text, list, number, checkbox, date, datetime) editable in UI, written back to YAML (Box: metadata templates on notes) 📦
- [x] O6.3 Special properties: `tags` (merged with #tags), `aliases` (used by link resolution/autocomplete)
- [x] O6.4 All-properties view: every property used across notes with counts; rename a property everywhere
- [x] O6.5 Search/filter by property values

## O7. Search and navigation
- [x] O7.1 Quick switcher: Cmd/Ctrl+O fuzzy jump to a note by title/alias, create if missing
- [x] O7.2 Command palette: Cmd/Ctrl+P fuzzy list of every action
- [ ] O7.3 Search operators: `tag:`, `folder:`, `task:`, `"exact phrase"`
- [ ] O7.5 Search results with matching lines in context (expandable), sort options, result count
- [x] O7.6 Saved searches (ties to bookmarks)
- [ ] O7.8 Outline panel: heading tree of the current note, click to jump
- [ ] O7.9 Navigation history: back/forward between notes
- [x] O7.10 Keyboard shortcuts, a fixed well-chosen set (new note, search, cycle notes) (Bear 8.2) (Apple: Cmd+N new note, Cmd+Shift+N new folder, Cmd+L checklist, Cmd+Shift+T/H/J title/heading/subheading, Cmd+U underline) 🍎

## O9. Daily notes, templates, productivity
- [ ] O9.1 Daily notes: "today's note" command, configurable title format and template, folder
- [ ] O9.2 Periodic navigation: previous/next daily note, calendar picker (could use the Calendar package)
- [ ] O9.3 Templates: notes in a templates folder; insert with variables `{{title}}`, `{{date}}`, `{{time}}`, custom formats (Box: template gallery) 📦
- [ ] O9.4 New note from template
- [ ] O9.5 Note composer: merge notes; extract selection into a new note, leaving a link or embed
- [ ] O9.6 Tasks view: all open/done checklist items across notes, filters by tag/folder/note, toggle from the list
- [ ] O9.8 Due dates and reminders on checklist items (e.g. `- [ ] pay rent @2026-11-01`): shown in the Tasks view (O9.6), reminder notification before the due date (Box)

## O10. Canvas
- [ ] O10.1 Canvas: infinite whiteboard with cards (text, notes, images, links), connections, groups, colours 🧩 ⚠️ dep likely
- [ ] O10.2 Canvas stored as JSON Canvas format; export/import `.canvas`

## O11. Bases (database views)
- [ ] O11.1 Bases: table view over notes with columns from properties, filters, sorting, formulas 🧩
- [ ] O11.2 Cards view and group-by
- [ ] O11.3 Embed a base inside a note

## O12. History and safety
- [x] O12.1 Version history: snapshots on save (throttled), view a diff, restore (Bear 8.1) (Box: named versions, compare any two) 📦
- [ ] O12.5 Lock / encrypt individual notes (password; client-side encryption) 🧩 (Apple: encrypted at rest, list shows title only, auto-relock; details in A7) 🍎

## O13. Export and import
- [ ] O13.3 Export note to PDF (print stylesheet, or server-side render ⚠️ dep) 🍎 📦
- [ ] O13.4 Export note as `.md`, export everything as `.zip` (Bear 7.1) (Apple: also export a single folder) 🍎
- [ ] O13.5 Import `.md` files and folders keeping structure, links and tags (Bear 7.2)
- [ ] O13.6 Importers: Bear, Evernote (.enex), Notion, Apple Notes, HTML 🧩 (Apple Notes import keeps folders, checklists, attachments) 🍎

## O14. Appearance and settings
- [x] O14.1 Settings page (via coleus/settings, like health): editor defaults, fonts, line width, attachment folder, daily note format, etc.
- [ ] O14.2 Font family / size (including the default font size for notes) and accent colour settings; zoom

## O15. Deep links and clipping
- [ ] O15.1 URI/deep links: open note, search, create note via URL parameters (Apple: "copy link to note") 🍎
- [ ] O15.3 Web clipper endpoint: save a URL/selection as a note 🍎

## O16. Mobile
- [ ] O16.1 Mobile layout polish: swipe between sidebar/list/editor, mobile toolbar above the keyboard

## A1. Folders and organization (Apple Notes)
- [x] A1.1 Smart Folders: saved rule sets (tags with any/all, created/edited date ranges, has checklists, has attachments, has drawings/scans, locked, folder) shown in the sidebar with counts
- [x] A1.2 Tag browser with multi-select: pick several tags to filter by all of them (AND)
- [x] A1.3 Group list by date: Pinned, Today, Yesterday, Previous 7 Days, Previous 30 Days, then month/year sections
- [ ] A1.4 Gallery view: grid of note cards with thumbnails (first image, sketch or text preview), toggle list/gallery per folder
- [x] A1.6 Note count per folder / tag / smart folder in the sidebar
- [ ] A1.8 Swipe actions on mobile list rows: pin, move, lock, delete

## A2. Rich text formatting (Apple Notes)
- [x] A2.1 Paragraph styles menu: Title, Heading, Subheading, Body, Monostyled, mapped to Markdown (`#`, `##`, `###`, plain, code) (Box: H1–H3, paragraph, quote) 📦
- [x] A2.2 Default style for the first line of a new note (Title / Heading / Subheading / Body) setting
- [x] A2.3 Underline and strikethrough in toolbar and shortcuts (Cmd/Ctrl+U, Cmd/Ctrl+Shift+X) 📦
- [x] A2.4 Text highlight colours (pink, purple, orange, mint, blue), stored as a safe Markdown extension (e.g. `==text=={color}`) 📦
- [x] A2.5 Dashed list and numbered list buttons; block quote button
- [x] A2.6 Emoji picker in the toolbar 📦

## A3. Checklists (Apple Notes)
- [x] A3.1 Checklist button converts the selected lines to checklist items (have the toolbar button; add: works on multi-line selection) and toggles back
- [x] A3.2 Automatically move checked items to the bottom of their list (setting + per-note action)
- [x] A3.3 Check All / Uncheck All / Delete Checked Items actions for a list
- [x] A3.4 Drag to reorder checklist items in the preview
- [x] A3.5 Indent checklist items into sub-tasks
- [x] A3.6 Checking items directly in the editor (not only in preview), with a strike-through look for done items

## A4. Attachments and media (Apple Notes)
- [ ] A4.1 Rich link previews: pasting a URL shows a card (title, description, image) fetched server-side safely (no SSRF, timeouts, cached) 📦
- [ ] A4.3 Scan documents: take photos (camera on mobile), crop/straighten, save as a multi-page PDF attachment 🧩 ⚠️ dep likely
- [ ] A4.4 Scan text / Live Text: OCR text from an image into the note 🧩 ⚠️ dep (OCR engine, e.g. Tesseract)
- [ ] A4.5 Search inside attachments: OCR text in images/scans and text in PDFs becomes searchable 🧩 ⚠️ dep
- [ ] A4.6 Sketches/drawings: a drawing canvas (pen, marker, pencil, eraser, colours, ruler, lasso) saved as an inline drawing 🧩 ⚠️ dep likely
- [ ] A4.7 Handwriting recognition: drawings become searchable text 🧩 ⚠️ dep
- [ ] A4.8 Audio recording with live transcript shown in the note, playback with transcript highlight 🧩 ⚠️ dep (speech-to-text) (builds on O3.6)
- [ ] A4.9 Audio/transcript summary (AI) 🧩 ⚠️ dep

## A6. Links between notes (Apple Notes)
- [ ] A6.1 `>>` shortcut: typing `>>` opens a note picker and inserts a link to that note (creates `[[Title]]`)

## A7. Security (Apple Notes)
- [ ] A7.1 Notes password management: set, change, reset (reset only affects future locks), password hint
- [ ] A7.3 Locked notes are left out of search content, snippets, backlinks and exports until unlocked

## A8. Copy as (Apple Notes)
- [ ] A8.6 Copy a note as Markdown / plain text / HTML

## A9. Capture from anywhere (Apple Notes)
- [ ] A9.1 Quick Note: global shortcut / floating button anywhere in Coleus opens a small note composer saved to a "Quick Notes" folder
- [ ] A9.2 Quick Note with context: when started from another Coleus page, store and show a link back to that page
- [ ] A9.3 Home dashboard widget: pinned/recent notes and a "new note" button (coleus/widgets package)
- [ ] A9.4 Voice dictation into the editor (browser speech recognition, where supported)

## A10. Search (Apple Notes)
- [ ] A10.1 Suggested searches: Locked notes, Notes with checklists, Notes with tags, Notes with drawings, Notes with scanned documents, Notes with attachments
- [ ] A10.2 Search scoped to the current folder vs all notes
- [ ] A10.3 Search results grouped: Top Hits, then notes, then attachments (with OCR matches) (needs A4.5)

## A11. Writing tools and AI (Apple Notes)
- [ ] A11.1 Writing Tools: proofread, rewrite (friendly/professional/concise), summarize, key points, list, table on a selection or note (Claude API) 🧩 ⚠️ dep (Anthropic SDK or HTTP client config) 📦
- [ ] A11.2 Summarize a note / audio transcript into a summary block at the top 📦
- [ ] A11.3 Image generation from text in a note (Image Playground equivalent) ⚠️ dep (image model API)
- [ ] A11.4 Smart reply / compose: draft text from a prompt inside the note

## A12. Print (Apple Notes)
- [ ] A12.1 Print a note (print stylesheet: hides chrome, keeps checkboxes/tables/images) 📦

## A13. Undo history (Apple Notes)
- [x] A13.1 Undo/redo history that survives switching notes in a session

## B5. Date chips (Box Notes)
- [ ] B5.4 Date chips: insert a date (`@today`, date picker) rendered as a chip, clickable to the Calendar app

## B6. Embeds and integrations (Box Notes)
- [ ] B6.1 Smart embeds via an oEmbed allow-list: YouTube, Vimeo, Loom, Figma, Miro, Google Maps, rendered in sandboxed iframes 🧩
- [ ] B6.2 Embed other Coleus items: calendar events, files, health entries, as live cards
- [ ] B6.3 Meeting notes: create a note from a Calendar event (title, date, attendees pre-filled; linked both ways)

## B7. Recents (Box Notes)
- [x] B7.4 Recents: notes recently opened by me (view tracking), separate from recently edited

## B8. Read-only lock (Box Notes)
- [ ] B8.7 Read-only lock: make a note read-only so it can't be changed by accident; unlock any time

## B9. AI on notes (Box Notes)
- [ ] B9.1 Ask questions about the open note (answers cite the passages) (Claude API) ⚠️ dep (same as A11.1)
- [ ] B9.2 Ask questions across all my notes (retrieval over FULLTEXT search results, answers link to the source notes) 🧩
- [ ] B9.3 Generate a note from a prompt or template (meeting agenda, project brief), inserted as editable Markdown
- [ ] B9.4 Translate a note or selection

## B10. Import and export (Box Notes)
- [ ] B10.1 Export a note as Word (.docx) ⚠️ dep (e.g. phpoffice/phpword)
- [ ] B10.2 Import Word (.docx) files ⚠️ dep

## Not planned (platform-specific, no equivalent here)
- Community plugin marketplace and theme marketplace (no plugin runtime in a hosted app)
- Local plain-file vaults on disk / "open in default app" / "reveal in Finder" (notes live in the database; export/import covers portability)
- Apple platform features: Siri, Spotlight, Handoff, Lock Screen / Control Center / Shortcuts actions, Apple Pencil specifics
  (pressure, Smart Script), Genmoji, phone-call recording, iCloud account storage. A9 capture and O15 API/deep links
  cover the useful parts on the web.
- Box platform features: Box Drive desktop sync, Box Sign, Box Shield ML threat detection, Box Relay marketplace, Box Canvas,
  enterprise SSO / user provisioning (users come from coleus/users). B6 covers the parts that apply here.

## Removed on purpose (don't re-propose)
- Workspace and tabs: tabs, split/linked panes, pop-out windows, saved workspaces, right-sidebar layout, stacked tabs (was O8)
- Sharing with other people: shared notes and folders, share links, roles and link settings, access stats, external guests,
  activity highlights of other people's edits (was O13.2, A8.1–A8.5, B3)
- Live sync, offline editing and installable PWA (was O12.3, O12.4, O16.2)
- Math Notes (was A5)
- CodeMirror editor: live preview, syntax highlighting, multiple cursors, Vim mode (was O4.1, O4.2, O4.9, O4.10, B5.5)
- Graph views (was O2.11, O2.12)
- Anything for other people: publishing, comments, presence, assigned tasks, notifications and activity feeds, governance
  (audit log, retention, legal hold, labels, watermarks, admin settings), emailing a copy, automations (was O13.1, B1, B2,
  B4.1/.2/.4, B6.4, B6.5, B7.1–.3, B7.5, B8.1–.6, B9.5, A8.6 email part)
- Multiple vaults, web viewer pane, REST API, plugin hooks, storage quota, resumable uploads (was O1.9, O15.2, O15.4, O15.5, O3.1b, O3.1g resume)
- Power-user extras: random note, Zettelkasten IDs, block references, orphans report, regex search, query blocks, slides,
  snapshot settings, custom CSS and themes, line numbers, pins per folder, paper styles, text colour, alignment,
  full-width toggle, map cards, passkey unlock, Box Notes import (was O1.7, O1.8, O2.3, O2.13, O7.4, O7.7, O9.7, O12.2,
  O14.3, O14.4, A1.5, A2.7, A2.8, B5.1–.3, A4.2, A7.2, B10.2 .boxnote)
