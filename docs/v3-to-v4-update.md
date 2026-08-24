# Updating from v3 to v4

Release 4.0.0 raises the PHP requirement and follows `joomla/http` to PSR-7 responses.

## At a glance

| | v3 (3.0.1) | v4 (4.0.0) |
|---|---|---|
| PHP | `^8.1.0` | `^8.3.0` |
| Response objects | `Joomla\Http\Response` with public properties | PSR-7 `ResponseInterface` |
| Public API | — | unchanged |

## Minimum supported PHP version raised

All Framework packages now require **PHP 8.3** or newer.

## Responses are PSR-7 objects

`joomla/http` 4.0 returns PSR-7 responses, so the internal handling changed:

```php
// v3
$xml = simplexml_load_string($response->body);
$headers['Cookie'] .= '; ' . $response->headers['Set-Cookie'];

// v4
$xml = simplexml_load_string($response->getBody()->getContents());
$responseHeaders = $response->getHeaders();
$headers['Cookie'] .= '; ' . implode(';', $responseHeaders['Set-Cookie']);
```

Note the second line: PSR-7 models a header as a **list of values**, so `Set-Cookie` is an array
rather than a string.

For your own code this matters if you subclassed an endpoint class or called
`AbstractMediawikiObject::validateResponse()` directly. `validateResponse()` still returns a
`SimpleXMLElement`, so callers of the public endpoint methods are unaffected.

If you passed a custom `Joomla\Mediawiki\Http` and inspected responses yourself:

```php
// v3
$status = $response->code;
$body   = $response->body;

// v4
$status = $response->getStatusCode();
$body   = (string) $response->getBody();
```

Prefer `(string) $response->getBody()` over `getBody()->getContents()` — the cast rewinds the
stream, so a second read still returns the body.

## Dependency changes

| Package | v3 (3.0.1) | v4 (4.0.0) |
|---|---|---|
| `php` | `^8.1.0` | `^8.3.0` |
| `joomla/http` | `^3.0` | `^4.0` |
| `joomla/registry` | `^3.0` | `^4.0` |
| `joomla/uri` | `^3.0` | `^4.0` |
