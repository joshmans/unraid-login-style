# Login Style

An Unraid plugin that gives the web UI's login page a dark look: a colour preset (Midnight, Graphite or Ember) or your own four colours, an optional background image and your own logo.

Unraid has no setting for the login page, so the plugin adds one include line to the login page file (`/usr/local/emhttp/plugins/dynamix/include/.login.php`) when it installs and at every boot, and takes that line out again when you uninstall or turn the style off. It only restyles the page. How signing in works is not touched.

Open **Settings > User Preferences > Login Style**.

## Settings

- **Use this style on the login page.** Off puts the stock login page back at once, without uninstalling.
- **Mode.** *Always dark*, or *Follow Unraid's theme*, which uses a light version when Unraid is on a light theme.
- **Colours.** Midnight (navy and blue-teal), Graphite (neutral grey) or Ember (the Unraid orange on near-black), or Custom: page top, page bottom, and the two ends of the accent gradient.
- **Background image.** An `https://` address, or the full path of a file on the server (PNG, JPEG, WebP, GIF or SVG, under 1.5 MB, in `/boot`, `/mnt` or `/tmp`). A file is read when you save and kept inside the style, because the login page is shown to people who are not signed in and can't be given a path on your server. A slider sets how much of the page colour is laid over the image.
- **Logo.** Same kind of value; replaces the Unraid wordmark in the banner. Empty keeps the wordmark.

The page shows a preview of the saved settings. Sign out, or use a private window, to see the real thing.

## Safety

- After every edit the login page is syntax-checked, and put back unchanged if it does not parse. A broken login page would lock you out of the web UI, so this check cannot be skipped.
- The plugin never replaces the page from a backup. It adds and removes only its own line, so an Unraid update that changed the page is never overwritten. (A copy of the stock page of each Unraid version it patched is kept in `/boot/config/plugins/unraid-login-style/backup/` for reference.)
- If the page has an unexpected shape (no single `</head>`), nothing is changed.
- If the style fails for any reason, the stock login page is shown.
- Images are limited to `https://` addresses and files you choose; values are checked before they go into the style, so a stray quote or bracket can't break out of it.
- It makes no network connections of its own. An image address you enter is loaded by the visitor's browser.

## Install

In Unraid: Plugins > Install Plugin, and paste

```
https://raw.githubusercontent.com/joshmans/unraid-login-style/main/unraid-login-style.plg
```

Works on Unraid 7.2 and newer. Developed and tested on 7.4.0-rc.1; the tests check the patch against that version's login page.

## Uninstall

Remove it from the Plugins page. That takes the line out of the login page. Your settings stay on the flash drive (`/boot/config/plugins/unraid-login-style/config.json`) in case you reinstall.

## Tests

`tests/run.sh` (needs only `php-cli`). `tests/apply_test.php` runs the patch against copies of real login pages from Unraid releases (`tests/fixtures/`) and checks apply, a second apply, removal byte for byte, and that removal after an OS update keeps the update. To add a release, copy its `.login.php` to `tests/fixtures/login.<version>.php`.

## License

MIT.
