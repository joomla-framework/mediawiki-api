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

`login()` performs the two-step token exchange the Action API requires.

> **The credentials travel in the query string.** `Users::login()` builds the request as
> `?action=login&lgname=…&lgpassword=…`, so the password appears in the wiki's access log, in any
> proxy log along the way, and in a `Referer` header. It is also inserted without URL-encoding, so
> a password containing `&`, `#` or a space produces a malformed request — or injects additional
> API parameters.
>
> Use a dedicated bot password with the narrowest possible grants
> ([Special:BotPasswords](https://www.mediawiki.org/wiki/Manual:Bot_passwords)), never a real
> account password, and treat the wiki's request log as containing that secret.

The same applies to `api.username` and `api.password` in the options:
`AbstractMediawikiObject::fetchUrl()` writes them into the URL's userinfo, so they become part of
the URL string as well.

## Options

| Option | Meaning |
|---|---|
| `api.url` | The wiki's script path, e.g. `https://en.wikipedia.org/w` |
| `api.username` | Optional HTTP-level user |
| `api.password` | Optional HTTP-level password |

Options are a `Joomla\Registry\Registry`. A second constructor argument accepts a
`Joomla\Mediawiki\Http` instance if you want to configure the transport.

## Tokens

Every write method (`editPage()`, `movePageByName()`, `blockUser()`, …) first fetches a token with
`action=query&meta=tokens`. `getToken()` on `Pages` and `Users` does the same and still accepts the
names of the old `intoken` parameter: `edit`, `delete`, `undelete`, `move`, `protect`, `block`,
`unblock`, `email`, `emailuser`, `import` and `options` all return the `csrf` token; `rollback`,
`userrights`, `watch`, `patrol`, `login` and `createaccount` return their own token. Its `$user`
argument is no longer used.

The `$intoken` argument of `Pages::getPageInfo()` and the `$rctoken` argument of
`Sites::getRecentChanges()` are deprecated and will be removed in 5.0: MediaWiki no longer supports
these parameters. Use `getToken()` instead.

## Things to know before you build on this

**Pass values as they are.** Every value is percent-encoded (RFC 3986) before it is added to the
query string, so a page title or search term containing `&`, `#`, `=` or `+` — all legal in
MediaWiki titles — is sent as a value. Do not encode values yourself; they would be encoded twice.
Multi-value parameters are joined with `|`, so a single value must not contain `|`.

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

**The login flow is the pre-1.27 one.** `action=login` with a name and password was replaced by
`action=clientlogin` and bot passwords. It still works for bot passwords in the `User@BotName`
form, but not for normal accounts on a current wiki.

**No `maxlag`, no rate limiting, no continuation.** The client sends no `maxlag` parameter — which
the Wikimedia projects expect from automated clients — has no backoff, and does not follow the
`continue` token, so paginated queries have to be looped by the caller.

**Errors are all `DomainException`.** The MediaWiki error code (`badtoken`, `ratelimited`,
`permissiondenied`, `readonly`) is not preserved in a form you can branch on; only the message text
is. That message comes from the remote server, so escape it before displaying it.
