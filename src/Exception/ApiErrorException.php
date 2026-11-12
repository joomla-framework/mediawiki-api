<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Exception;

/**
 * Exception thrown when the MediaWiki API answers with an error.
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiErrorException extends \DomainException
{
    /**
     * Constructor.
     *
     * @param   string           $apiCode   The error code reported by the API, e.g. `badtoken` or `maxlag`.
     * @param   string           $message   The error message reported by the API.
     * @param   ?\Throwable      $previous  The previous exception.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly string $apiCode, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the error code reported by the API, e.g. `badtoken`, `ratelimited`, `maxlag` or `readonly`.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getApiCode(): string
    {
        return $this->apiCode;
    }
}
