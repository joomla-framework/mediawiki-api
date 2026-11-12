<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki;

use Joomla\Http\Http as BaseHttp;
use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP client class for connecting to a MediaWiki instance.
 *
 * @since  1.0
 */
class Http extends BaseHttp
{
    /**
     * Constructor.
     *
     * @param   array                $options    Client options object.
     * @param   ?TransportInterface  $transport  The HTTP transport object.
     *
     * @since   12.3
     */
    public function __construct($options = [], TransportInterface $transport = null)
    {
        parent::__construct($options, $transport);

        // Make sure the user agent string is defined.
        if (!isset($this->options['userAgent'])) {
            $this->options['userAgent'] = 'JMediawiki/1.0';
        }

        // Set the default timeout to 120 seconds.
        if (!isset($this->options['timeout'])) {
            $this->options['timeout'] = 120;
        }
    }

    /**
     * Method to send the POST command to the server.
     *
     * MediaWiki treats a boolean parameter as true whenever it is present, whatever its value. In an
     * array of data, `false` and `null` values are therefore left out and `true` is sent as `1`.
     *
     * @param   string|UriInterface  $url      The URI to the resource to request.
     * @param   mixed                $data     Either an associative array or a string to be sent with the request.
     * @param   array                $headers  An array of request headers to send with the request.
     * @param   integer              $timeout  Read timeout in seconds.
     *
     * @return  Response
     *
     * @since   __DEPLOY_VERSION__
     */
    public function post($url, $data, array $headers = [], $timeout = null)
    {
        if (\is_array($data)) {
            $data = array_map(
                static fn ($value) => $value === true ? '1' : $value,
                array_filter($data, static fn ($value) => $value !== false && $value !== null)
            );
        }

        return parent::post($url, $data, $headers, $timeout);
    }
}
