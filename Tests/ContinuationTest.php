<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Mediawiki\Categories;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Images;
use Joomla\Mediawiki\Links;
use Joomla\Mediawiki\Pages;
use Joomla\Mediawiki\Users;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that continuation values are sent to MediaWiki.
 */
class ContinuationTest extends TestCase
{
    /**
     * Methods with a continuation argument, the arguments before it and the query string before it.
     *
     * @return  array
     */
    public static function methods(): array
    {
        return [
            'Categories::getCategories' => [Categories::class, 'getCategories', [['A'], null, null, null], 'action=query&prop=categories&titles=A&clcontinue='],
            'Categories::getCategoriesInfo' => [Categories::class, 'getCategoriesInfo', [['A']], 'action=query&prop=categoryinfo&titles=A&cicontinue='],
            'Images::getImages' => [Images::class, 'getImages', [['A'], null], 'action=query&prop=images&titles=A&imcontinue='],
            'Images::getImageInfo' => [Images::class, 'getImageInfo', [null, null, null, null, null, null, null, null], 'action=query&prop=imageinfo&iicontinue='],
            'Links::getIWLinks' => [Links::class, 'getIWLinks', [['A'], false, null], 'action=query&prop=iwlinks&titles=A&iwcontinue='],
            'Links::getLangLinks' => [Links::class, 'getLangLinks', [['A'], null], 'action=query&prop=langlinks&titles=A&llcontinue='],
            'Links::enumerateLinks' => [Links::class, 'enumerateLinks', [], 'action=query&list=alllinks&alcontinue='],
            'Pages::getPageInfo' => [Pages::class, 'getPageInfo', [['A'], null, null], 'action=query&prop=info&titles=A&incontinue='],
            'Pages::getPageProperties' => [Pages::class, 'getPageProperties', [['A']], 'action=query&prop=pageprops&titles=A&ppcontinue='],
            'Pages::getPageTemplates' => [Pages::class, 'getPageTemplates', [['A'], null, null], 'action=query&prop=templates&titles=A&tlcontinue='],
            'Pages::getBackLinks' => [Pages::class, 'getBackLinks', ['A', null], 'action=query&list=backlinks&bltitle=A&blcontinue='],
            'Pages::getIWBackLinks' => [Pages::class, 'getIWBackLinks', ['A', null], 'action=query&list=iwbacklinks&iwbltitle=A&iwblcontinue='],
            'Users::getUserContribs' => [Users::class, 'getUserContribs', [null, null, null, null, null], 'action=query&list=usercontribs&uccontinue='],
        ];
    }

    /**
     * Call a method and check the URL it requests.
     *
     * @param   string  $class      The endpoint class.
     * @param   string  $method     The method.
     * @param   array   $arguments  The arguments.
     * @param   string  $query      The expected query string, without format=xml.
     *
     * @return  void
     */
    private function assertRequest(string $class, string $method, array $arguments, string $query): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with('/api.php?' . $query . '&format=xml')
            ->willReturn(new Response('data://text/plain,<api/>', 200));

        (new $class(new Registry(), $client))->$method(...$arguments);
    }

    /**
     * The continuation value returned by the previous response is sent, encoded.
     *
     * @param   string  $class      The endpoint class.
     * @param   string  $method     The method.
     * @param   array   $arguments  The arguments before the continuation argument.
     * @param   string  $query      The expected query string up to the continuation value.
     *
     * @return  void
     */
    #[DataProvider('methods')]
    public function testContinuationValueIsSent(string $class, string $method, array $arguments, string $query): void
    {
        $this->assertRequest($class, $method, [...$arguments, '736|Foo Bar'], $query . '736%7CFoo%20Bar');
    }

    /**
     * Passing true still sends the parameter without a value.
     *
     * @param   string  $class      The endpoint class.
     * @param   string  $method     The method.
     * @param   array   $arguments  The arguments before the continuation argument.
     * @param   string  $query      The expected query string up to the continuation value.
     *
     * @return  void
     */
    #[DataProvider('methods')]
    public function testTrueSendsAnEmptyValue(string $class, string $method, array $arguments, string $query): void
    {
        $this->assertRequest($class, $method, [...$arguments, true], $query);
    }
}
