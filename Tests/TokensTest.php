<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Pages;
use Joomla\Mediawiki\Sites;
use Joomla\Mediawiki\Users;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests that tokens are requested with action=query&meta=tokens.
 */
class TokensTest extends TestCase
{
    /**
     * Mock client object.
     *
     * @var    Http&MockObject
     */
    private Http&MockObject $client;

    /**
     * Sets up the fixture.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        $this->client = $this->createMock(Http::class);
    }

    /**
     * A response carrying all token types.
     *
     * @return  Response
     */
    private function tokenResponse(): Response
    {
        return new Response(
            'data://text/plain,<api><query><tokens csrftoken="CSRF" rollbacktoken="ROLLBACK" userrightstoken="RIGHTS" '
            . 'watchtoken="WATCH"/></query></api>',
            200
        );
    }

    /**
     * Token names and the token type they are requested as.
     *
     * @return  array
     */
    public static function tokenTypes(): array
    {
        return [
            'edit'       => ['edit', 'csrf', 'CSRF'],
            'move'       => ['move', 'csrf', 'CSRF'],
            'delete'     => ['delete', 'csrf', 'CSRF'],
            'undelete'   => ['undelete', 'csrf', 'CSRF'],
            'protect'    => ['protect', 'csrf', 'CSRF'],
            'block'      => ['block', 'csrf', 'CSRF'],
            'unblock'    => ['unblock', 'csrf', 'CSRF'],
            'emailuser'  => ['emailuser', 'csrf', 'CSRF'],
            'csrf'       => ['csrf', 'csrf', 'CSRF'],
            'rollback'   => ['rollback', 'rollback', 'ROLLBACK'],
            'userrights' => ['userrights', 'userrights', 'RIGHTS'],
            'watch'      => ['watch', 'watch', 'WATCH'],
        ];
    }

    /**
     * getToken() maps the old intoken names to the token types of meta=tokens.
     *
     * @param   string  $name      The name passed to getToken().
     * @param   string  $type      The token type requested.
     * @param   string  $expected  The token returned.
     *
     * @return  void
     */
    #[DataProvider('tokenTypes')]
    public function testGetTokenUsesMetaTokens(string $name, string $type, string $expected): void
    {
        $this->client->expects($this->exactly(2))
            ->method('get')
            ->with('/api.php?action=query&meta=tokens&type=' . $type . '&format=xml')
            ->willReturnCallback(fn () => $this->tokenResponse());

        $this->assertSame($expected, (new Pages(new Registry(), $this->client))->getToken('Main Page', $name));
        $this->assertSame($expected, (new Users(new Registry(), $this->client))->getToken('Jane', $name));
    }

    /**
     * The $intoken parameter of getPageInfo() is deprecated.
     *
     * @return  void
     */
    #[IgnoreDeprecations]
    public function testGetPageInfoIntokenIsDeprecated(): void
    {
        $this->client->method('get')->willReturn(new Response('data://text/plain,<api/>', 200));

        $this->expectUserDeprecationMessageMatches('/The \$intoken parameter of .*getPageInfo\(\) is deprecated/');

        (new Pages(new Registry(), $this->client))->getPageInfo(['Main Page'], null, ['edit']);
    }

    /**
     * The $rctoken parameter of getRecentChanges() is deprecated.
     *
     * @return  void
     */
    #[IgnoreDeprecations]
    public function testGetRecentChangesRctokenIsDeprecated(): void
    {
        $this->client->method('get')->willReturn(new Response('data://text/plain,<api/>', 200));

        $this->expectUserDeprecationMessageMatches('/The \$rctoken parameter of .*getRecentChanges\(\) is deprecated/');

        (new Sites(new Registry(), $this->client))->getRecentChanges(null, null, null, null, null, null, null, null, ['patrol']);
    }
}
