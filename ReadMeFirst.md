# Read Me First

## ⚠️ Do NOT use the green "Code" button to download this plugin

The green **Code > Download ZIP** button (and the auto-generated "Source code"
files on the Releases page) will **not** install correctly in Moodle. They
wrap everything in an extra folder named after this repo, and Moodle requires
the plugin's top-level folder to be named exactly `reportcard`.

## ✅ Install from the Releases page instead

1. Go to the **[Releases](../../releases)** page for this repo.
2. Under the latest release, download **`block_reportcard.zip`** from the
   **Assets** list.
3. In Moodle, log in as a site administrator and go to:
   **Site administration > Plugins > Install plugins**
4. Drag in `block_reportcard.zip` (or click to browse and select it).
5. Click **Install plugin from the ZIP file**, then **Continue** on the
   plugin check screen.
6. Click **Upgrade Moodle database now** to finish.
7. Turn on editing on the Dashboard, then **Add a block > Grade Display**.

No FTP, SSH, or direct file access is needed — the whole install happens
through the Moodle admin UI.

## More details

See [`reportcard/README.md`](reportcard/README.md) in this repo for full
documentation, including how to link a parent/mentor account to a student.
