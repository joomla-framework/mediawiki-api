<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Users;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Tests the login flow of the Users class against a recording transport.
 */
class UsersLoginTest extends TestCase
{
    /**
     * The requests sent through the transport.
     *
     * @var    array
     */
    private array $requests = [];

    /**
     * The responses the transport returns, in order.
     *
     * @var    Response[]
     */
    private array $responses = [];

    /**
     * Create a Users object whose HTTP client records every request.
     *
     * @return  Users
     */
    private function createUsers(): Users
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('request')
            ->willReturnCallback(
                function ($method, $uri, $data = null, array $headers = []) {
                    $this->requests[] = [
                        'method'  => $method,
                        'url'     => (string) $uri,
                        'data'    => $data,
                        'headers' => $headers,
                    ];

                    return array_shift($this->responses);
                }
            );

        $options = new Registry(['api' => ['url' => 'https://wiki.example.org/w']]);

        return new Users($options, new Http([], $transport));
    }

    /**
     * Build a response.
     *
     * @param   string  $xml      The body.
     * @param   array   $headers  The response headers.
     *
     * @return  Response
     */
    private function xmlResponse(string $xml, array $headers = []): Response
    {
        return new Response('data://text/plain,' . rawurlencode($xml), 200, $headers);
    }

    /**
     * Queue the responses of a successful login.
     *
     * @return  void
     */
    private function queueLoginResponses(): void
    {
        $this->responses[] = $this->xmlResponse(
            '<api><query><tokens logintoken="abc+\\"/></query></api>',
            ['Set-Cookie' => ['wiki_session=s1; path=/; secure; HttpOnly']]
        );
        $this->responses[] = $this->xmlResponse(
            '<api><login result="Success" lguserid="1" lgusername="Bot"/></api>',
            ['set-cookie' => ['wikiUserID=1; path=/', 'wikiUserName=Bot; path=/', 'wiki_session=s2; path=/']]
        );
    }

    /**
     * The credentials are posted in the body, not put into the URL.
     *
     * @return  void
     */
    public function testLoginPostsTheCredentialsInTheBody(): void
    {
        $users = $this->createUsers();
        $this->queueLoginResponses();

        $result = $users->login('Bot@task', 'p&ss#word+1', 'example');

        $this->assertSame('Success', (string) $result->login['result']);
        $this->assertCount(2, $this->requests);

        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame(
            'https://wiki.example.org/w/api.php?action=query&meta=tokens&type=login&format=xml',
            $this->requests[0]['url']
        );

        $this->assertSame('POST', $this->requests[1]['method']);
        $this->assertSame('https://wiki.example.org/w/api.php?action=login&format=xml', $this->requests[1]['url']);
        $this->assertSame(
            [
                'lgname'     => 'Bot@task',
                'lgpassword' => 'p&ss#word+1',
                'lgtoken'    => 'abc+\\',
                'lgdomain'   => 'example',
            ],
            $this->requests[1]['data']
        );
    }

    /**
     * The session cookies reach the login request and every later request through the client.
     *
     * @return  void
     */
    public function testLoginSessionCookiesAreSentWithLaterRequests(): void
    {
        $users = $this->createUsers();
        $this->queueLoginResponses();
        $this->responses[] = $this->xmlResponse('<api><query><userinfo id="1" name="Bot"/></query></api>');

        $users->login('Bot@task', 'secret');

        $this->assertSame('wiki_session=s1', $this->requests[1]['headers']['Cookie']);

        $users->getCurrentUserInfo();

        $this->assertSame(
            'wiki_session=s2; wikiUserID=1; wikiUserName=Bot',
            $this->requests[2]['headers']['Cookie']
        );
    }

    /**
     * Cookies already configured on the client are kept.
     *
     * @return  void
     */
    public function testLoginKeepsConfiguredCookies(): void
    {
        $users = $this->createUsers();
        $this->queueLoginResponses();

        $client = (new \ReflectionProperty($users, 'client'))->getValue($users);
        $client->setOption('headers', ['Cookie' => 'consent=yes', 'X-Test' => '1']);

        $users->login('Bot@task', 'secret');

        $this->assertSame('consent=yes; wiki_session=s1', $this->requests[1]['headers']['Cookie']);
        $this->assertSame(
            ['Cookie' => 'consent=yes; wiki_session=s2; wikiUserID=1; wikiUserName=Bot', 'X-Test' => '1'],
            $client->getOption('headers')
        );
    }
}
