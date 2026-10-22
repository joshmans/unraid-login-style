# Login Style

## 2026.10.21b

First version.

- Restyles the Unraid login page dark, in **Settings > User Preferences > Login Style**: pick Midnight, Graphite or Ember, or set your own four colours.
- Optional background image (an https address, or a file on the server) and your own logo in place of the Unraid wordmark.
- "Always dark" or "follow Unraid's theme" (light when Unraid is on a light theme). A switch turns the style off at once without uninstalling.
- Unraid has no setting for the login page, so the plugin adds one include line to the page file at install and at every boot, checks that the page still parses, and takes the line out again when you uninstall or switch the plugin off. It never replaces the page from a backup, so an Unraid update is not overwritten.
- If the style fails for any reason, the stock login page is shown.
- Works on Unraid 7.2 and newer.
