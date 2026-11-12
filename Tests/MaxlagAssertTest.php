<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Sites;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Tests the api.maxlag and api.assert options.
 */
class MaxlagAssertTest extends TestCase
{
    /**
     * Request the site info with the given options and check the URL.
     *
     * @param   array   $options   The API options.
     * @param   string  $expected  The expected URL.
     *
     * @return  void
     */
    private function assertRequest(array $options, string $expected): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with($expected)
            ->willReturn(new Response('data://text/plain,<api/>', 200));

        (new Sites(new Registry(['api' => $options]), $client))->getSiteInfo();
    }

    /**
     * Without the options, neither parameter is sent.
     *
     * @return  void
     */
    public function testNotSentByDefault(): void
    {
        $this->assertRequest([], '/api.php?action=query&meta=siteinfo&format=xml');
    }

    /**
     * maxlag and assert are added to every request.
     *
     * @return  void
     */
    public function testMaxlagAndAssertAreSent(): void
    {
        $this->assertRequest(['maxlag' => 5, 'assert' => 'bot'], '/api.php?action=query&meta=siteinfo&format=xml&maxlag=5&assert=bot');
    }

    /**
     * maxlag 0 is a valid value and is sent.
     *
     * @return  void
     */
    public function testMaxlagZeroIsSent(): void
    {
        $this->assertRequest(['maxlag' => 0], '/api.php?action=query&meta=siteinfo&format=xml&maxlag=0');
    }
}
