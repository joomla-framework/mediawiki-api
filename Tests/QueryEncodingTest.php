<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Mediawiki\Categories;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Pages;
use Joomla\Mediawiki\Search;
use Joomla\Mediawiki\Users;
use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/stubs/AbstractMediawikiObjectMock.php';

/**
 * Tests that request parameters are percent-encoded into the query string.
 */
class QueryEncodingTest extends TestCase
{
    /**
     * The URLs the transport received.
     *
     * @var    string[]
     */
    private array $urls = [];

    /**
     * Create a client that records the URL of every request.
     *
     * @return  Http
     */
    private function createClient(): Http
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('request')
            ->willReturnCallback(
                function ($method, $uri) {
                    $this->urls[] = (string) $uri;

                    return new Response('data://text/plain,<api/>', 200);
                }
            );

        return new Http([], $transport);
    }

    /**
     * Create the options for a wiki.
     *
     * @return  Registry
     */
    private function createOptions(): Registry
    {
        return new Registry(['api' => ['url' => 'https://wiki.example.org/w']]);
    }

    /**
     * Titles containing query string delimiters are sent as values.
     *
     * @return  void
     */
    public function testTitlesAreEncoded(): void
    {
        $pages = new Pages($this->createOptions(), $this->createClient());

        $pages->getPageInfo(['Foo&Bar#x', 'A+B=C', 'Main Page']);

        $this->assertSame(
            ['https://wiki.example.org/w/api.php?action=query&prop=info&titles=Foo%26Bar%23x|A%2BB%3DC|Main%20Page&format=xml'],
            $this->urls
        );
    }

    /**
     * A search term cannot inject further API parameters.
     *
     * @return  void
     */
    public function testSearchTermCannotInjectParameters(): void
    {
        $search = new Search($this->createOptions(), $this->createClient());

        $search->search('x&action=delete&title=Main_Page');

        $this->assertSame(
            ['https://wiki.example.org/w/api.php?action=query&list=search&srsearch=x%26action%3Ddelete%26title%3DMain_Page&format=xml'],
            $this->urls
        );
    }

    /**
     * Scalar values and lists are encoded in every endpoint class.
     *
     * @return  void
     */
    public function testScalarAndListValuesAreEncoded(): void
    {
        $client = $this->createClient();

        (new Categories($this->createOptions(), $client))->getCategoryMembers('Category:A&B', null, 10, null, [0, 1]);
        (new Users($this->createOptions(), $client))->getUserContribs('Jane Doe#1');

        $this->assertSame(
            [
                'https://wiki.example.org/w/api.php?action=query&list=categorymembers&cmtitle=Category%3AA%26B&cmlimit=10&cmnamespace=0|1&format=xml',
                'https://wiki.example.org/w/api.php?action=query&list=usercontribs&ucuser=Jane%20Doe%231&format=xml',
            ],
            $this->urls
        );
    }

    /**
     * fetchUrl() adds the query separator when the path has none.
     *
     * @return  void
     */
    public function testFetchUrlWithoutQuery(): void
    {
        $object = new AbstractMediawikiObjectMock($this->createOptions(), $this->createClient());

        $this->assertSame('https://wiki.example.org/w/api.php?format=xml', $object->fetchUrl(''));
        $this->assertSame('https://wiki.example.org/w/api.php?action=query&format=xml', $object->fetchUrl('?action=query'));
    }
}
