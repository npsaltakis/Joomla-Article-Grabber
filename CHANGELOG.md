# Changelog

All notable changes to **Joomla Article Grabber** are documented here.
This project adheres to semantic versioning.

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
