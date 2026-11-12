<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Sites;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Tests that api.username and api.password are sent as an Authorization header.
 */
class HttpCredentialsTest extends TestCase
{
    /**
     * The requests sent through the transport.
     *
     * @var    array
     */
    private array $requests = [];

    /**
     * Create a Sites object whose HTTP client records every request.
     *
     * @param   array  $options  The API options.
     *
     * @return  Sites
     */
    private function createSites(array $options): Sites
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('request')
            ->willReturnCallback(
                function ($method, $uri, $data = null, array $headers = []) {
                    $this->requests[] = ['url' => (string) $uri, 'headers' => $headers];

                    return new Response('data://text/plain,<api/>', 200);
                }
            );

        $options = new Registry(['api' => $options + ['url' => 'https://wiki.example.org/w']]);

        return new Sites($options, new Http(['headers' => ['X-Test' => '1']], $transport));
    }

    /**
     * A password containing '@' and '/' no longer changes the host the request goes to.
     *
     * @return  void
     */
    public function testCredentialsAreSentAsHeader(): void
    {
        $this->createSites(['username' => 'bob', 'password' => 'p@ss/x'])->getSiteInfo();

        $this->assertSame('https://wiki.example.org/w/api.php?action=query&meta=siteinfo&format=xml', $this->requests[0]['url']);
        $this->assertSame('Basic ' . base64_encode('bob:p@ss/x'), $this->requests[0]['headers']['Authorization']);
        $this->assertSame('1', $this->requests[0]['headers']['X-Test']);
    }

    /**
     * Without a user name, no Authorization header is added.
     *
     * @return  void
     */
    public function testNoHeaderWithoutUsername(): void
    {
        $this->createSites([])->getSiteInfo();

        $this->assertSame('https://wiki.example.org/w/api.php?action=query&meta=siteinfo&format=xml', $this->requests[0]['url']);
        $this->assertArrayNotHasKey('Authorization', $this->requests[0]['headers']);
    }
}
