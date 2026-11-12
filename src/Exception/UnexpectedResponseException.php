<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Exception;

/**
 * Exception thrown when the server answers with an HTTP error status or with a body that is not valid XML.
 *
 * The exception code is the HTTP status code.
 *
 * @since  __DEPLOY_VERSION__
 */
class UnexpectedResponseException extends \DomainException
{
}
