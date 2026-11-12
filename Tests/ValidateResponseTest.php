<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Mediawiki\Exception\ApiErrorException;
use Joomla\Mediawiki\Exception\ApiWarningException;
use Joomla\Mediawiki\Exception\UnexpectedResponseException;
use Joomla\Mediawiki\Http;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/stubs/AbstractMediawikiObjectMock.php';

/**
 * Tests for AbstractMediawikiObject::validateResponse().
 */
class ValidateResponseTest extends TestCase
{
    /**
     * Validate a response.
     *
     * @param   string    $body     The response body.
     * @param   integer   $status   The HTTP status code.
     * @param   Registry  $options  The options.
     *
     * @return  \SimpleXMLElement
     */
    private function validate(string $body, int $status = 200, ?Registry $options = null): \SimpleXMLElement
    {
        $object = new AbstractMediawikiObjectMock($options ?? new Registry(), $this->createMock(Http::class));

        return $object->validateResponse(new Response('data://text/plain,' . rawurlencode($body), $status));
    }

    /**
     * A body that is not XML raises an exception instead of returning false, and emits no PHP warning.
     *
     * @return  void
     */
    public function testInvalidXmlThrows(): void
    {
        try {
            $this->validate('<html><body><h1>502 Bad Gateway</h1><hr></body>', 502);
            $this->fail('No exception thrown');
        } catch (UnexpectedResponseException $e) {
            $this->assertSame(502, $e->getCode());
            $this->assertStringContainsString('not valid XML', $e->getMessage());
        }

        $this->assertSame([], libxml_get_errors());
    }

    /**
     * An HTTP error status raises an exception even when the body is XML.
     *
     * @return  void
     */
    public function testHttpErrorStatusThrows(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionCode(503);

        $this->validate('<api/>', 503);
    }

    /**
     * An API error keeps its code and message.
     *
     * @return  void
     */
    public function testApiErrorKeepsCodeAndMessage(): void
    {
        try {
            $this->validate('<api><error code="badtoken" info="Invalid CSRF token."/></api>');
            $this->fail('No exception thrown');
        } catch (ApiErrorException $e) {
            $this->assertInstanceOf(\DomainException::class, $e);
            $this->assertSame('badtoken', $e->getApiCode());
            $this->assertSame('Invalid CSRF token.', $e->getMessage());
        }
    }

    /**
     * All warnings are reported, as strings.
     *
     * @return  void
     */
    public function testWarningsAreReported(): void
    {
        try {
            $this->validate(
                '<api><warnings><main xml:space="preserve">Unrecognized parameter: foo.</main>'
                . '<query xml:space="preserve">Bar is deprecated.</query></warnings><query/></api>'
            );
            $this->fail('No exception thrown');
        } catch (ApiWarningException $e) {
            $this->assertInstanceOf(\DomainException::class, $e);
            $this->assertSame(['main: Unrecognized parameter: foo.', 'query: Bar is deprecated.'], $e->getWarnings());
            $this->assertSame("main: Unrecognized parameter: foo.\nquery: Bar is deprecated.", $e->getMessage());
        }
    }

    /**
     * With api.throwOnWarnings disabled, the result is returned with its warnings.
     *
     * @return  void
     */
    public function testWarningsCanBeIgnored(): void
    {
        $xml = $this->validate(
            '<api><warnings><main xml:space="preserve">Unrecognized parameter: foo.</main></warnings><query/></api>',
            200,
            new Registry(['api' => ['throwOnWarnings' => false]])
        );

        $this->assertSame('Unrecognized parameter: foo.', (string) $xml->warnings->main);
    }

    /**
     * A valid response is returned.
     *
     * @return  void
     */
    public function testValidResponseIsReturned(): void
    {
        $this->assertSame('Joomla', (string) $this->validate('<api><query><page title="Joomla"/></query></api>')->query->page['title']);
    }
}
