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
use Joomla\Mediawiki\Search;
use Joomla\Mediawiki\Sites;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Checks the module and parameter names of every read request against the MediaWiki API help
 * (api.php?action=help&modules=...), with every optional argument set.
 */
class RequestParametersTest extends TestCase
{
    /**
     * Requests and the query string they must produce.
     *
     * @return  array
     */
    public static function requests(): array
    {
        return [
            'Categories::getCategories' => [
                Categories::class, 'getCategories',
                [['Main Page'], ['sortkey', 'timestamp'], ['!hidden'], 10, true, ['Category:A'], 'descending'],
                'action=query&prop=categories&titles=Main%20Page&clprop=sortkey|timestamp&clshow=%21hidden&cllimit=10'
                . '&clcontinue=&clcategories=Category%3AA&cldir=descending',
            ],
            'Categories::getCategoriesUsed' => [
                Categories::class, 'getCategoriesUsed',
                [['Main Page']],
                'action=query&generator=categories&prop=info&titles=Main%20Page',
            ],
            'Categories::getCategoriesInfo' => [
                Categories::class, 'getCategoriesInfo',
                [['Category:A'], true],
                'action=query&prop=categoryinfo&titles=Category%3AA&cicontinue=',
            ],
            'Categories::getCategoryMembers' => [
                Categories::class, 'getCategoryMembers',
                ['Category:A', null, 10, ['ids', 'title'], [0, 14], ['page', 'subcat'], 's', 'e', 'sk', 'ek', 'skp', 'ekp', 'timestamp', 'desc', 'page|1'],
                'action=query&list=categorymembers&cmtitle=Category%3AA&cmlimit=10&cmprop=ids|title&cmnamespace=0|14'
                . '&cmtype=page|subcat&cmstart=s&cmend=e&cmstartsortkey=sk&cmendsortkey=ek&cmstartsortkeyprefix=skp'
                . '&cmendsortkeyprefix=ekp&cmsort=timestamp&cmdir=desc&cmcontinue=page%7C1',
            ],
            'Categories::enumerateCategories' => [
                Categories::class, 'enumerateCategories',
                ['A', 'Z', 'Jo', 'descending', 1, 9, 10, ['size', 'hidden']],
                'action=query&list=allcategories&acfrom=A&acto=Z&acprefix=Jo&acdir=descending&acmin=1&acmax=9&aclimit=10'
                . '&acprop=size|hidden',
            ],
            'Categories::getChangeTags' => [
                Categories::class, 'getChangeTags',
                [['name', 'displayname'], 10],
                'action=query&list=tags&tgprop=name|displayname&tglimit=10',
            ],
            'Images::getImages' => [
                Images::class, 'getImages',
                [['Main Page'], 10, true, 'File:A.png', 'descending'],
                'action=query&prop=images&titles=Main%20Page&imlimit=10&imcontinue=&imimages=File%3AA.png&imdir=descending',
            ],
            'Images::getImagesUsed' => [
                Images::class, 'getImagesUsed',
                [['Main Page']],
                'action=query&generator=images&prop=info&titles=Main%20Page',
            ],
            'Images::getImageInfo' => [
                Images::class, 'getImageInfo',
                [['url', 'size'], 5, 's', 'e', 100, 50, 'latest', 'page1', true, ['File:A.png']],
                'action=query&prop=imageinfo&titles=File%3AA.png&iiprop=url|size&iilimit=5&iistart=s&iiend=e&iiurlwidth=100'
                . '&iiurlheight=50&iimetadataversion=latest&iiurlparam=page1&iicontinue=',
            ],
            'Images::enumerateImages' => [
                Images::class, 'enumerateImages',
                ['A', 'Z', 'Jo', 1, 99, 10, 'descending', 'abc', 'def', ['url'], 'image/png'],
                'action=query&list=allimages&aifrom=A&aito=Z&aiprefix=Jo&aiminsize=1&aimaxsize=99&ailimit=10&aidir=descending'
                . '&aisha1=abc&aisha1base36=def&aiprop=url&aimime=image%2Fpng',
            ],
            'Links::getLinks' => [
                Links::class, 'getLinks',
                [['Main Page'], [0], 10, 'p|1', ['Help:A'], 'descending'],
                'action=query&prop=links&titles=Main%20Page&plnamespace=0&pllimit=10&plcontinue=p%7C1&pltitles=Help%3AA'
                . '&pldir=descending',
            ],
            'Links::getLinksUsed' => [
                Links::class, 'getLinksUsed',
                [['Main Page']],
                'action=query&generator=links&prop=info&titles=Main%20Page',
            ],
            'Links::getIWLinks' => [
                Links::class, 'getIWLinks',
                [['Main Page'], true, 10, true, 'de', 'Haupt', 'descending'],
                'action=query&prop=iwlinks&titles=Main%20Page&iwurl=&iwlimit=10&iwcontinue=&iwprefix=de&iwtitle=Haupt'
                . '&iwdir=descending',
            ],
            'Links::getLangLinks' => [
                Links::class, 'getLangLinks',
                [['Main Page'], 10, true, 1, 'de', 'Haupt', 'descending'],
                'action=query&prop=langlinks&titles=Main%20Page&lllimit=10&llcontinue=&llurl=1&lllang=de&lltitle=Haupt'
                . '&lldir=descending',
            ],
            'Links::enumerateLinks' => [
                Links::class, 'enumerateLinks',
                [true, 'A', 'Z', 'Jo', 1, ['ids', 'title'], 0, 10],
                'action=query&list=alllinks&alcontinue=&alfrom=A&alto=Z&alprefix=Jo&alunique=1&alprop=ids|title'
                . '&alnamespace=0&allimit=10',
            ],
            'Pages::getIWBackLinks' => [
                Pages::class, 'getIWBackLinks',
                ['Haupt', 'de', true, 10, ['iwprefix']],
                'action=query&list=iwbacklinks&iwbltitle=Haupt&iwblprefix=de&iwblcontinue=&iwbllimit=10&iwblprop=iwprefix',
            ],
            'Search::search' => [
                Search::class, 'search',
                ['joomla', [0], 'text', ['totalhits'], ['size'], true, 20, 10],
                'action=query&list=search&srsearch=joomla&srnamespace=0&srwhat=text&srinfo=totalhits&srprop=size'
                . '&srredirects=&sroffset=20&srlimit=10',
            ],
            'Search::openSearch' => [
                Search::class, 'openSearch',
                ['joo', 5, [0], 1, 'json'],
                'action=opensearch&search=joo&limit=5&namespace=0&suggest=1',
            ],
            'Sites::getSiteInfo' => [
                Sites::class, 'getSiteInfo',
                [['general', 'namespaces'], 'local', true, true, ['de']],
                'action=query&meta=siteinfo&siprop=general|namespaces&sifilteriw=local&sishowalldb=&sinumberingroup='
                . '&siinlanguagecode=de',
            ],
            'Sites::getEvents' => [
                Sites::class, 'getEvents',
                [['ids'], 'block', 'block/block', 'User:A', 'User:', 'tag', 'Admin', 's', 'e', 'newer', 10],
                'action=query&list=logevents&leprop=ids&letype=block&leaction=block%2Fblock&letitle=User%3AA&leprefix=User%3A'
                . '&letag=tag&leuser=Admin&lestart=s&leend=e&ledir=newer&lelimit=10',
            ],
            'Sites::getRecentChanges' => [
                Sites::class, 'getRecentChanges',
                ['s', 'e', 'newer', [0, 1], 'Admin', 'Bot', 'tag', ['title'], null, ['!bot'], 10, 'edit', 1],
                'action=query&list=recentchanges&rcstart=s&rcend=e&rcdir=newer&rcnamespace=0|1&rcuser=Admin&rcexcludeuser=Bot'
                . '&rctag=tag&rcprop=title&rcshow=%21bot&rclimit=10&rctype=edit&rctoponly=1',
            ],
            'Sites::getProtectedTitles' => [
                Sites::class, 'getProtectedTitles',
                [[0], ['sysop'], 10, 'older', 's', 'e', ['level']],
                'action=query&list=protectedtitles&ptnamespace=0&ptlevel=sysop&ptlimit=10&ptdir=older&ptstart=s&ptend=e'
                . '&ptprop=level',
            ],
        ];
    }

    /**
     * Every read request uses the module and parameter names MediaWiki expects.
     *
     * @param   string  $class      The endpoint class.
     * @param   string  $method     The method to call.
     * @param   array   $arguments  The arguments.
     * @param   string  $query      The expected query string, without format=xml.
     *
     * @return  void
     */
    #[DataProvider('requests')]
    public function testRequestUsesTheDocumentedParameters(string $class, string $method, array $arguments, string $query): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with('/api.php?' . $query . '&format=xml')
            ->willReturn(new Response('data://text/plain,<api/>', 200));

        (new $class(new Registry(), $client))->$method(...$arguments);
    }
}
