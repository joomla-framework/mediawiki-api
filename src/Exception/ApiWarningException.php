<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Exception;

/**
 * Exception thrown when the MediaWiki API answers with warnings and `api.throwOnWarnings` is enabled.
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiWarningException extends \DomainException
{
    /**
     * Constructor.
     *
     * @param   string[]     $warnings  The warnings, each prefixed with the module that raised it.
     * @param   ?\Throwable  $previous  The previous exception.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly array $warnings, ?\Throwable $previous = null)
    {
        parent::__construct(implode("\n", $warnings), 0, $previous);
    }

    /**
     * Get the warnings, each prefixed with the module that raised it (`main: Unrecognized parameter: foo.`).
     *
     * @return  string[]
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
