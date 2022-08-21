<?php
/**
 * Part of the Joomla Framework Mediawiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use PHPUnit\Framework\TestCase;
use Joomla\Mediawiki\Http;
use Joomla\Registry\Registry;

require_once __DIR__ . '/stubs/AbstractMediawikiObjectMock.php';

/**
 * Test class for AbstractMediawikiObject.
 *
 * @since       1.0
 */
class AbstractMediawikiObjectTest extends TestCase
{
	/**
	 * @var    Registry  Options for the Mediawiki object.
	 * @since  1.0
	 */
	protected $options;

	/**
	 * @var    \Joomla\Mediawiki\Http  Mock client object.
	 * @since  1.0
	 */
	protected $client;

	/**
	 * @var    \Joomla\Mediawiki\Tests\AbstractMediawikiObjectMock  Object under test.
	 * @since  1.0
	 */
	protected $object;

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
		$this->options = new Registry;

		$errorLevel = error_reporting();
		error_reporting($errorLevel & ~E_DEPRECATED);

		$this->client = $this->createMock(Http::class);

		error_reporting($errorLevel);

		$this->object = new AbstractMediawikiObjectMock($this->options, $this->client);
	}

	/**
	 * Tests the buildParameter method
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testBuildParameter()
	{
		$this->assertThat(
			$this->object->buildParameter(['Joomla', 'Joomla', 'Joomla']),
			$this->equalTo('Joomla|Joomla|Joomla')
		);
	}

}
