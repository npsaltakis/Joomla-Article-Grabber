<p align="center">
  <img src="logo.svg" alt="Joomla Article Grabber" width="128" height="128">
</p>

<h1 align="center">Joomla Article Grabber</h1>

A Joomla 5 / 6 administrator component to transfer `com_content` articles (with their
images) between your Joomla sites.

## Two modes

- **REST pull** — add remote sites (URL + Joomla API token, stored **encrypted**), browse
  their articles over the Web Services API and pull selected ones into a chosen category/author.
- **XML export/import** — download an article as XML on one site and upload it on another.

Images (intro/full + inline) are downloaded locally and their paths rewritten automatically.

## Screenshots

**Pull articles from a remote site** — pick a source, browse its articles (paginated) and pull the selected ones into a local category/author:

![Pull Remote Articles](screenshots/1-remote-pull.png)

**Manage remote sources** (API tokens stored encrypted) with a one-click connection test:

![Sources](screenshots/2-sources.png)

![Edit source and test connection](screenshots/3-source-test-connection.png)

**Import history** — every pull/import is logged with its source, author and image results:

![Import History](screenshots/4-history.png)

**Built-in updates** via Extensions → Update:

![Updates](screenshots/5-update.png)

## Requirements

- Joomla 5.x or 6.x
- PHP 8.1+
- For REST pull, the **remote** site must have the *Web Services - Articles* and
  *API Authentication - Joomla Token* plugins enabled, and a user API token.

## Install

Extensions → Install → Upload the `com_content_api_grabber-x.y.z.zip` package.

## Updates (maintainers)

Releases are automated via GitHub Actions:

```bash
# bump nothing by hand — just tag and push
git tag v1.2.0
git push origin v1.2.0
```

The workflow builds the package, publishes a GitHub Release, syncs the manifest `<version>`
and regenerates `update.xml`. Sites that have the component installed then see the update
under **Extensions → Update**.

> Set the `<updateservers>` URL in `content_api_grabber.xml` to the raw URL of `update.xml`
> on your repository's `main` branch.

## License

GNU GPL v2 or later.
