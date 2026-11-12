<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Mediawiki\Http;
use Joomla\Mediawiki\Pages;
use Joomla\Registry\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Pages.
 *
 * @since  1.0
 */
class PagesTest extends TestCase
{
    /**
     * @var    Registry  Options for the Mediawiki object.
     * @since  1.0
     */
    protected $options;

    /**
     * @var    \Joomla\Http\Http&MockObject  Mock client object.
     * @since  1.0
     */
    protected $client;

    /**
     * @var    Pages  Object under test.
     * @since  1.0
     */
    protected $object;

    /**
     * @var    \Joomla\Http\Response  Response object.
     * @since  1.0
     */
    protected $response;

    /**
     * @var    string  Sample xml string.
     * @since  1.0
     */
    protected $sampleString = '<a><b></b><c></c></a>';

    /**
     * @var    string  Sample xml error message.
     * @since  1.0
     */
    protected $errorString = '<message>Generic Error</message>';

    /**
     * A response carrying the token that the write methods fetch first.
     *
     * @var    string
     */
    protected $tokenString = '<a><query><pages><page edittoken="EDITTOKEN" movetoken="MOVETOKEN" '
        . 'deletetoken="DELETETOKEN" undeletetoken="UNDELETETOKEN" protecttoken="PROTECTTOKEN" '
        . 'rollbacktoken="ROLLBACKTOKEN"></page></pages></query></a>';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     *
     * @return void
     *
     * @since  1.0
     */
    protected function setUp(): void
    {
        $this->options = new Registry();

        $this->client   = $this->createMock(Http::class);
        $this->response = new Response('data://text/plain,' . $this->sampleString, 200);

        $this->object = new Pages($this->options, $this->client);
    }

    /**
     * Build a response object carrying the given XML.
     *
     * @param   string   $xml   The body.
     * @param   integer  $code  The status code.
     *
     * @return  Response
     */
    protected function xmlResponse(string $xml, int $code = 200): Response
    {
        return new Response('data://text/plain,' . $xml, $code);
    }

    /**
     * Expect a token request followed by the write request, and capture the posted data.
     *
     * @param   string  $expectedPath  The path the write request must use.
     * @param   array   &$captured     Filled with the data posted by the write request.
     *
     * @return  void
     */
    protected function expectTokenThenPost(string $expectedPath, ?array &$captured): void
    {
        $tokenResponse = $this->xmlResponse($this->tokenString);
        $writeResponse = $this->response;

        $this->client->expects($this->exactly(2))
            ->method('post')
            ->willReturnCallback(
                function ($url, $data) use ($expectedPath, &$captured, $tokenResponse, $writeResponse) {
                    if (str_contains($url, 'intoken=')) {
                        return $tokenResponse;
                    }

                    $this->assertSame($expectedPath, $url);
                    $captured = $data;

                    return $writeResponse;
                }
            );
    }

    /**
     * Tests the getPageInfo method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetPageInfo()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=info&titles=Main Page&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getPageInfo(['Main Page']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getPageInfo method with its optional parameters
     *
     * @return void
     */
    public function testGetPageInfoWithOptions()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=info&titles=Main Page|Joomla&inprop=protection&intoken=edit&incontinue=&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getPageInfo(['Main Page', 'Joomla'], ['protection'], ['edit'], true),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getPageProperties method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetPageProperties()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=pageprops&titles=Main Page&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getPageProperties(['Main Page']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getRevisions method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetRevisions()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=revisions&titles=Main Page&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getRevisions(['Main Page']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getRevisions method with its optional parameters
     *
     * @return void
     */
    public function testGetRevisionsWithOptions()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=revisions&titles=Main Page&rvprop=ids|timestamp&rvparse=&rvlimit=10&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getRevisions(['Main Page'], ['ids', 'timestamp'], true, 10),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getPageTemplates method
     *
     * @return void
     */
    public function testGetPageTemplates()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&prop=templates&titles=Main Page&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getPageTemplates(['Main Page']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getBackLinks method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetBackLinks()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=backlinks&bltitle=Joomla&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getBackLinks('Joomla'),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getIWBackLinks method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetIWBackLinks()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=iwbacklinks&iwbltitle=Joomla&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getIWBackLinks('Joomla'),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests that getToken reads the requested token out of the response
     *
     * @return void
     */
    public function testGetToken()
    {
        $this->client->expects($this->once())
            ->method('post')
            ->with('/api.php?action=query&prop=info&intoken=edit&titles=User:Admin&format=xml', null)
            ->willReturn($this->xmlResponse($this->tokenString));

        $this->assertSame('EDITTOKEN', $this->object->getToken('Admin', 'edit'));
    }

    /**
     * Tests that editPage passes the section title through rather than repeating the section
     *
     * @return void
     */
    public function testEditPageSendsTheSectionTitle()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=edit&format=xml', $captured);

        $this->object->editPage('Main Page', '2', 'New section title', 'Body text', 'Edit summary');

        $this->assertSame('Main Page', $captured['title']);
        $this->assertSame('EDITTOKEN', $captured['token']);
        $this->assertSame('2', $captured['section']);
        $this->assertSame(
            'New section title',
            $captured['sectiontitle'],
            'sectiontitle must carry the $sectiontitle argument, not the section number'
        );
        $this->assertSame('Body text', $captured['text']);
        $this->assertSame('Edit summary', $captured['summary']);
    }

    /**
     * Tests that movePageByName sends the destination title as "to"
     *
     * @return void
     */
    public function testMovePageByNameSendsTheDestination()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=move&format=xml', $captured);

        $this->object->movePageByName('Old title', 'New title', 'Because');

        $this->assertSame('Old title', $captured['from']);
        $this->assertSame(
            'New title',
            $captured['to'],
            'to must carry the destination title, not the reason'
        );
        $this->assertSame('Because', $captured['reason']);
        $this->assertSame('MOVETOKEN', $captured['token']);
    }

    /**
     * Tests that movePageByID sends the destination title as "to"
     *
     * @return void
     */
    public function testMovePageByIdSendsTheDestination()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=move&format=xml', $captured);

        $this->object->movePageByID(42, 'New title', 'Because');

        $this->assertSame(42, $captured['fromid']);
        $this->assertSame(
            'New title',
            $captured['to'],
            'to must carry the destination title, not the reason'
        );
        $this->assertSame('Because', $captured['reason']);
    }

    /**
     * Tests that rollback sends the summary under the summary key
     *
     * @return void
     */
    public function testRollbackSendsTheSummary()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=rollback&format=xml', $captured);

        $this->object->rollback('Main Page', 'Vandal', 'Reverting vandalism');

        $this->assertSame('Main Page', $captured['title']);
        $this->assertSame('Vandal', $captured['user']);
        $this->assertSame(
            'Reverting vandalism',
            $captured['summary'],
            'The summary must be sent as summary - action=rollback has no expiry parameter'
        );
        $this->assertArrayNotHasKey('expiry', $captured);
    }

    /**
     * Tests the deletePageByName method
     *
     * @return void
     */
    public function testDeletePageByName()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=delete&format=xml', $captured);

        $this->object->deletePageByName('Main Page', 'Spam');

        $this->assertSame('Main Page', $captured['title']);
        $this->assertSame('Spam', $captured['reason']);
        $this->assertSame('DELETETOKEN', $captured['token']);
    }

    /**
     * Tests the deletePageByID method
     *
     * @return void
     */
    public function testDeletePageById()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=delete&format=xml', $captured);

        $this->object->deletePageByID(42, 'Spam');

        $this->assertSame(42, $captured['pageid']);
        $this->assertSame('Spam', $captured['reason']);
    }

    /**
     * Tests the undeletePage method
     *
     * @return void
     */
    public function testUndeletePage()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=undelete&format=xml', $captured);

        $this->object->undeletePage('Main Page', 'Restored', '2026-01-01T00:00:00Z');

        $this->assertSame('Main Page', $captured['title']);
        $this->assertSame('Restored', $captured['reason']);
        $this->assertSame('2026-01-01T00:00:00Z', $captured['timestamp']);
    }

    /**
     * Tests the changeProtection method
     *
     * @return void
     */
    public function testChangeProtection()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=protect&format=xml', $captured);

        $this->object->changeProtection('Main Page', 'edit=sysop', 'never', 'High traffic');

        $this->assertSame('Main Page', $captured['title']);
        $this->assertSame('edit=sysop', $captured['protections']);
        $this->assertSame('never', $captured['expiry']);
        $this->assertSame('High traffic', $captured['reason']);
        $this->assertSame(
            'PROTECTTOKEN',
            $captured['token'],
            'action=protect needs a protect token, not an unblock token'
        );
    }

    /**
     * Tests that an API error response raises a DomainException
     *
     * @return void
     */
    public function testAnErrorResponseRaisesADomainException()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->willReturn($this->xmlResponse('<a><error code="badtitle" info="Bad title"></error></a>'));

        $this->expectException(\DomainException::class);

        $this->object->getPageInfo(['Main Page']);
    }
}
