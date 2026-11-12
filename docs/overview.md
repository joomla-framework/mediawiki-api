# Overview

A client for the [MediaWiki Action API](https://www.mediawiki.org/wiki/API:Main_page). It groups
the endpoints into objects — pages, users, categories, links, images, search and site information —
reachable from one entry point.

```bash
composer require joomla/mediawiki
```

## Getting started

```php
use Joomla\Mediawiki\Mediawiki;
use Joomla\Registry\Registry;

$options = new Registry([
    'api.url' => 'https://en.wikipedia.org/w',
]);

$mediawiki = new Mediawiki($options);

$page = $mediawiki->pages->getPageInfo(['Joomla']);
```

`api.url` is the wiki's script path — the directory containing `api.php`, **without** the file
name. The client appends `/api.php` itself.

## The endpoint objects

Each is a lazily created property on `Mediawiki`:

| Property | Class | Covers |
|---|---|---|
| `$mediawiki->pages` | `Pages` | Page info, content, edit, delete, move, protect, rollback |
| `$mediawiki->users` | `Users` | Login, logout, user info, contributions, block/unblock |
| `$mediawiki->links` | `Links` | Links on a page, language links, external links, backlinks |
| `$mediawiki->categories` | `Categories` | Categories of a page, members of a category |
| `$mediawiki->images` | `Images` | Images on a page, image info, usage |
| `$mediawiki->search` | `Search` | Full-text search, opensearch |
| `$mediawiki->sites` | `Sites` | Site info, meta information |

```php
$mediawiki->pages->getPageInfo(['Main Page']);
$mediawiki->pages->getPageContent(['Main Page']);
$mediawiki->links->getLinks(['Main Page']);
$mediawiki->categories->getCategories(['Main Page']);
$mediawiki->search->search('joomla');
$mediawiki->sites->getSiteInfo();
```

Every method returns a `SimpleXMLElement` — the client requests `format=xml` and parses the
response with `validateResponse()`, which raises a `DomainException` when the API reports an error
or a warning.

## Authenticating

```php
$mediawiki->users->login('BotUser@BotName', $botPassword);

// … authenticated calls …

$mediawiki->users->logout();
```

`login()` fetches a login token with `action=query&meta=tokens&type=login` and then posts the
name, password, token and optional domain to `action=login` in the request body. The credentials
never appear in the URL. Since MediaWiki 1.27, `action=login` only accepts
[bot passwords](https://www.mediawiki.org/wiki/Manual:Bot_passwords): create one on
`Special:BotPasswords` with the narrowest grants you need and log in as `User@BotName`. Logging in
with the main account password this way is deprecated and refused by many wikis. Interactive
logins (`action=clientlogin`) and OAuth are not supported.

The session cookies the wiki sets are stored in the `Cookie` entry of the HTTP client's `headers`
option. `Mediawiki` hands the same client to every endpoint object, so all later requests made
through it are authenticated. Cookies already configured there are kept.

`api.username` and `api.password` in the options are a different matter: they are credentials for
HTTP authentication in front of the wiki (for example a password-protected staging server), not
a wiki account. They are sent as an HTTP Basic `Authorization` header, which the client stores in
its `headers` option; they are never written into the URL.

## Options

| Option | Meaning |
|---|---|
| `api.url` | The wiki's script path, e.g. `https://en.wikipedia.org/w` |
| `api.username` | Optional user for HTTP Basic authentication in front of the wiki |
| `api.password` | Optional password for HTTP Basic authentication; only used with `api.username` |

Options are a `Joomla\Registry\Registry`. A second constructor argument accepts a
`Joomla\Mediawiki\Http` instance if you want to configure the transport.

## Things to know before you build on this

**Request parameters are concatenated, not encoded.** Each endpoint class builds its query string
by string concatenation, so a page title containing `&`, `#` or `=` — all legal in MediaWiki
titles — changes the request rather than being sent as a value. Encode values before passing them
in:

```php
$mediawiki->pages->getPageInfo([rawurlencode($title)]);
```

**The XML parser is not hardened.** `validateResponse()` calls `simplexml_load_string()` without
`LIBXML_NONET`, without internal error handling, and without checking the return value. On invalid
XML it returns `false`, which the caller then treats as a `SimpleXMLElement`. Point the client only
at wikis you trust.

**The response format is fixed to XML.** `fetchUrl()` appends `format=xml` unconditionally.
MediaWiki has treated XML as deprecated in favour of JSON for years, so expect it to receive less
attention upstream over time.

**`buildParameter()` mis-joins some lists.** It walks the array with `next()` and a loose
comparison to decide where to place the `|` separator, so an element that is falsy — `'0'` or an
empty string — loses its separator. Build multi-value parameters with `implode('|', $values)`
yourself when the values are not guaranteed to be non-empty.

**No `maxlag`, no rate limiting, no continuation.** The client sends no `maxlag` parameter — which
the Wikimedia projects expect from automated clients — has no backoff, and does not follow the
`continue` token, so paginated queries have to be looped by the caller.

**Errors are all `DomainException`.** The MediaWiki error code (`badtoken`, `ratelimited`,
`permissiondenied`, `readonly`) is not preserved in a form you can branch on; only the message text
is. That message comes from the remote server, so escape it before displaying it.
