# JED listing — copy & checklist

Draft content to paste into the Joomla Extensions Directory submission form
(https://extensions.joomla.org). Edit as you like.

## Basics
- **Title:** Joomla Article Grabber
- **License:** GNU GPL v2 or later
- **Type:** Component (administrator)
- **Joomla compatibility:** 5.x, 6.x
- **PHP minimum:** 8.1
- **Price:** Free
- **Suggested category:** Migration & Conversion  (alt: Content Sharing)

## Required URLs
- **Download:** https://github.com/npsaltakis/Joomla-Article-Grabber/releases/latest
- **Support:** https://github.com/npsaltakis/Joomla-Article-Grabber/issues  
- **Documentation:** https://github.com/npsaltakis/Joomla-Article-Grabber#readme
- **Project/Website:** https://github.com/npsaltakis/Joomla-Article-Grabber

## Short description (one line)
Pull articles with their images from one Joomla site to another over the REST API — or via XML — choosing the target category and author.

## Long description
Joomla Article Grabber moves `com_content` articles, with all their images,
between your Joomla sites.

Two ways to transfer:

- **REST pull (recommended):** install it on the site that should RECEIVE
  content. Add one or more remote Joomla sites by their URL and an API token
  (the token is stored encrypted in the database). Browse the remote site's
  articles, pick the ones you want, and pull them into a local category and
  author of your choice. Intro/full images and inline images are downloaded
  locally and their paths rewritten automatically.
- **XML export/import:** export an article as an XML file on one site and
  upload it on another — handy when sites cannot reach each other.

Highlights:
- API tokens encrypted at rest (libsodium, keyed from the site secret)
- "Test connection" button to validate a source
- Paginated remote article browser
- Import History log (what was pulled, from where, by whom, image results)
- Duplicate-safe (unique alias handling)
- Built-in update server (one-click updates from Extensions → Update)
- Works on Joomla 5 and Joomla 6

Requirements on the REMOTE site: the *Web Services - Articles* and
*API Authentication - Joomla Token* plugins enabled, plus a user API token.

## Screenshots to capture (admin)
1. **Pull (Remote)** — source picker + remote article list + target category/author
2. **Sources** — list of configured remote sites
3. **Source edit** — URL + token + "Test connection" (show the green success)
4. **Import History** — the log table
5. (optional) Extensions → Update showing an available update

## Icon / logo
- A square logo (e.g. 128×128 or 200×200 PNG). A simple "grab/download arrow +
  Joomla" mark works.

## Submission steps (you do these — needs your Joomla account)
1. Log in at https://extensions.joomla.org with your joomla.org account.
2. First time only: register as a **Vendor** (name, logo, contact).
3. From the vendor dashboard: **Submit an Extension**.
4. Fill in the fields above, upload screenshots + icon, set URLs.
5. Submit for review. A JED volunteer reviews it (days–weeks); they may ask for
   small changes. Once approved it goes live.
