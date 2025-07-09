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
}
