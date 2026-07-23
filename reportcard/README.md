# Report Card block (block_reportcard)

A Moodle block that reads data straight from the Moodle **gradebook** and
shows, on a user's Dashboard, a report card with:

- Course name (linked to the course)
- Quarter 1 / Quarter 2 / Quarter 3 / Quarter 4 grade category totals
- Final Grade for the course

## What it does

- **Students** see only their own report card — one row per course they're
  enrolled in, with Quarter 1–4 and Final Grade pulled live from that
  course's gradebook.
- **Parents / mentors** (any account assigned a role in a student's user
  context — see "Additional info" below) see one report card section per
  linked student instead of their own data, with the student's name as a
  heading.
- **Everyone else** (no student role, no linked students) sees a
  "not enrolled / no students assigned" message instead of empty data.

Quarter columns are matched from your grade category names — any grade
category whose name contains "Quarter 1", "Quarter 2", "Quarter 3", or
"Quarter 4" (case-insensitive) is picked up automatically. No settings
page is needed; this is based on how your gradebook categories are
already named.

## Requirements

- Moodle 4.5.x (built against `$plugin->requires = 2024100700`)
- No other plugins required — this reads Moodle's own gradebook and
  enrolment tables directly.

## Installation (upload the ZIP — no file/folder access needed)

1. Log in as a site administrator.
2. Go to **Site administration > Plugins > Install plugins**.
3. Under "Install plugin from ZIP file," drag in `block_reportcard.zip`
   (or click to browse and select it).
4. Click **Install plugin from the ZIP file**. Moodle will validate it
   and show a plugin check screen — click **Continue**.
5. On the "Plugins check" page, click **Upgrade Moodle database now** to
   finish the install.
6. Turn on editing on the Dashboard, then **Add a block > Grade Display**
   (this is the block's display name — the plugin folder is
   `reportcard`).

No FTP, SSH, or direct file access is needed — this whole process happens
through the Moodle admin UI.

## Additional info: linking a parent/mentor to a student

Parents (or any "mentor" account) can be linked to one or more students so
that when *they* view the block, they see a separate section per linked
student, instead of the block being empty for them. This uses Moodle's
standard mechanism for linking one account to another (no custom field,
extra table, or extra capability is needed).

1. Go to the **student's** profile page.
2. **Preferences > Roles > This user's role assignment.**
3. Assign the parent/mentor's account to a role there (any role that is
   assignable at **User** context — e.g. a "Parent" role, if your site has
   one; if not, one can be created under
   **Site administration > Users > Permissions > Define roles**, ticking
   **User** under "Context types where this role may be assigned").

That's it — the parent doesn't need to be enrolled in the student's
courses. As soon as this role assignment exists, the parent's Report Card
block will automatically show that student's grades.

## Capabilities

- `block/reportcard:addinstance` — add the block to a course/system
  context (teachers & managers by default)
- `block/reportcard:myaddinstance` — add the block to one's own Dashboard
  (on by default, like other "my page" blocks)

## Privacy

This block implements Moodle's Privacy API as a `null_provider` — it
stores no personal data of its own. It only reads and displays data that
already exists in Moodle's gradebook and enrolment tables (which have
their own privacy providers).

## Notes / things worth knowing

- The block only appears on the **Dashboard** (`applicable_formats`
  limits it to `my`), since it's inherently per-user data.
- It uses `enrol_get_users_courses($userid, true)` — only **active**
  enrolments are included.
- Grade values are formatted using Moodle's own `grade_format_gradevalue()`,
  so they'll display using whatever grade display type (points, letter,
  percentage) each course/category is configured to use.

## File structure

```
reportcard/
├── block_reportcard.php             Main block class
├── version.php
├── db/
│   └── access.php                   Capabilities (addinstance/myaddinstance)
├── lang/en/
│   └── block_reportcard.php         Language strings
└── classes/
    └── privacy/
        └── provider.php             Privacy API (null_provider)
```
