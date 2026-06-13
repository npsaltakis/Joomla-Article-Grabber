# Changelog

All notable changes to **Joomla Article Grabber** are documented here.
This project adheres to semantic versioning.

## 1.2.0
- **Search & category filter** on the remote article list: search by title/alias
  (`filter[search]`) and filter by remote category (`filter[catid]`).
- **Tag transfer**: tags are read from the remote article (both direct and
  JSON:API `included` formats) and created locally when they do not yet exist.
- **Date preservation**: `created`, `publish_up` and `publish_down` are carried
  over from the source article (ISO 8601 dates normalised to MySQL format).
- **Featured flag**: new "Mark as featured" checkbox on the pull form; adds
  imported articles to `#__content_frontpage` via the core content model.
- **Skip already imported**: new "Skip already imported" checkbox; articles
  already present in the import log (same source URL + remote ID) are skipped.
  Pull summary now shows `Imported / Skipped / Failed` counts.
- **Category filter** loads remote categories from the source's
  `/api/v1/content/categories` endpoint; silently hidden if unavailable.

## 1.1.9
- Added **Import History** view: a log of every REST/XML import (source, author,
  image results, link to the created article) with delete-selected and clear-all.
- Remote pull form now pre-selects the source's saved **default category**.

## 1.1.8
- Added **Test connection** button on the source form (validates URL + token,
  using the stored encrypted token when the field is left blank).
- Added **pagination** to the remote article list (Prev/Next, page size).

## 1.1.7
- First public release.
- **REST pull**: add remote Joomla sites (URL + API token, token stored
  encrypted) and pull selected articles into a chosen category/author.
- **XML export/import** between Joomla sites.
- Images (intro/full + inline) are downloaded locally and paths rewritten;
  Joomla `#joomlaImage://` fragments handled.
- Unique-alias handling to avoid duplicates.
- GitHub-based auto-update (update server + release workflow).
- Verified on Joomla 5.4 and Joomla 6.1.
