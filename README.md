# Joomla Article Grabber

A Joomla 5 / 6 administrator component to transfer `com_content` articles (with their
images) between your Joomla sites.

## Two modes

- **REST pull** — add remote sites (URL + Joomla API token, stored **encrypted**), browse
  their articles over the Web Services API and pull selected ones into a chosen category/author.
- **XML export/import** — download an article as XML on one site and upload it on another.

Images (intro/full + inline) are downloaded locally and their paths rewritten automatically.

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
