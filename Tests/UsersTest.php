<?php

/**
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Joomla\Http\Response;
use Joomla\Mediawiki\Http;
use Joomla\Registry\Registry;
use Joomla\Mediawiki\Users;

/**
 * Test class for Users.
 *
 * @since  1.0
 */
class UsersTest extends TestCase
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
     * @var    Users  Object under test.
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

        $this->client = $this->createMock(Http::class);
        // Add methods ['get', 'post', 'delete', 'patch', 'put']
        $this->response = new Response('data://text/plain,' . $this->sampleString, 200);

        $this->object = new Users($this->options, $this->client);
    }

    /**
     * Tests the getUserInfo method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetUserInfo()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=users&ususers=Joomla&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getUserInfo(['Joomla']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getCurrentUserInfo method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetCurrentUserInfo()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&meta=userinfo&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getCurrentUserInfo(),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests the getUserContribs method
     *
     * @return void
     *
     * @since  1.0
     */
    public function testGetUserContribs()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=usercontribs&ucuser=Joomla&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getUserContribs('Joomla'),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
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
        $tokenResponse = $this->xmlResponse(
            '<api><query><tokens csrftoken="CSRFTOKEN" userrightstoken="RIGHTSTOKEN"/></query></api>'
        );
        $writeResponse = $this->response;

        $this->client->expects($this->once())
            ->method('get')
            ->with($this->stringStartsWith('/api.php?action=query&meta=tokens&type='))
            ->willReturn($tokenResponse);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturnCallback(
                function ($url, $data) use ($expectedPath, &$captured, $writeResponse) {
                    $this->assertSame($expectedPath, $url);
                    $captured = $data;

                    return $writeResponse;
                }
            );
    }

    /**
     * Tests that getUserInfo sends usprop as a proper key/value pair
     *
     * @return void
     */
    public function testGetUserInfoWithProperties()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=users&ususers=Joomla&usprop=blockinfo|groups&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getUserInfo(['Joomla'], ['blockinfo', 'groups']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests that getCurrentUserInfo sends uiprop as a proper key/value pair
     *
     * @return void
     */
    public function testGetCurrentUserInfoWithProperties()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&meta=userinfo&uiprop=rights|groups&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getCurrentUserInfo(['rights', 'groups']),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests that logout calls action=logout rather than action=login
     *
     * @return void
     */
    public function testLogoutCallsTheLogoutAction()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=logout&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->logout(),
            $this->equalTo(simplexml_load_string($this->sampleString))
        );
    }

    /**
     * Tests that blockUser calls action=block rather than action=unblock
     *
     * @return void
     */
    public function testBlockUserCallsTheBlockAction()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=block&format=xml', $captured);

        $this->object->blockUser('Vandal', 'never', 'Persistent vandalism', null, null, null, null, null, true);

        $this->assertSame('Vandal', $captured['user']);
        $this->assertSame('CSRFTOKEN', $captured['token']);
        $this->assertSame('never', $captured['expiry']);
        $this->assertSame('Persistent vandalism', $captured['reason']);
        $this->assertSame(
            true,
            $captured['allowusertalk'],
            'The MediaWiki parameter is allowusertalk, not allowusetalk'
        );
    }

    /**
     * Tests the unBlockUserByName method
     *
     * @return void
     */
    public function testUnBlockUserByName()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=unblock&format=xml', $captured);

        $this->object->unBlockUserByName('Vandal', 'Appeal accepted');

        $this->assertSame('Vandal', $captured['user']);
        $this->assertSame('Appeal accepted', $captured['reason']);
        $this->assertSame('CSRFTOKEN', $captured['token']);
    }

    /**
     * Tests the unBlockUserByID method
     *
     * @return void
     */
    public function testUnBlockUserById()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=unblock&format=xml', $captured);

        $this->object->unBlockUserByID(42, 'Appeal accepted');

        $this->assertSame(42, $captured['id']);
        $this->assertSame('Appeal accepted', $captured['reason']);
    }

    /**
     * Tests the assignGroup method
     *
     * @return void
     */
    public function testAssignGroup()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=userrights&format=xml', $captured);

        $this->object->assignGroup('Editor', 'sysop', 'bot', 'Promotion');

        $this->assertSame('Editor', $captured['user']);
        $this->assertSame('RIGHTSTOKEN', $captured['token']);
        $this->assertSame('sysop', $captured['add']);
        $this->assertSame('bot', $captured['remove']);
        $this->assertSame('Promotion', $captured['reason']);
    }

    /**
     * Tests the emailUser method
     *
     * @return void
     */
    public function testEmailUser()
    {
        $captured = null;
        $this->expectTokenThenPost('/api.php?action=emailuser&format=xml', $captured);

        $this->object->emailUser('Editor', 'Hello', 'Message body', true);

        $this->assertSame('Editor', $captured['target']);
        $this->assertSame('Hello', $captured['subject']);
        $this->assertSame('Message body', $captured['text']);
        $this->assertSame(true, $captured['ccme']);
    }

    /**
     * Tests the getUserContribs method with its optional parameters
     *
     * @return void
     */
    public function testGetUserContribsWithOptions()
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api.php?action=query&list=usercontribs&ucuser=Joomla&uclimit=10&ucdir=older&ucprop=ids|title&format=xml')
            ->willReturn($this->response);

        $this->assertThat(
            $this->object->getUserContribs('Joomla', null, 10, null, null, null, 'older', null, ['ids', 'title']),
            $this->equalTo(simplexml_load_string($this->sampleString))
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
            ->willReturn($this->xmlResponse('<a><error code="baduser" info="Bad user"></error></a>'));

        $this->expectException(\DomainException::class);

        $this->object->getUserInfo(['Joomla']);
    }
}
