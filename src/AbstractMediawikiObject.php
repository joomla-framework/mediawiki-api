<?php

/**
 * Part of the Joomla Framework Mediawiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki;

use Joomla\Http\Response;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;

/**
 * Mediawiki API object class for the Joomla Framework.
 *
 * @since  1.0
 */
abstract class AbstractMediawikiObject
{
    /**
     * @var    Registry  Options for the Mediawiki object.
     * @since  1.0
     */
    protected $options;

    /**
     * @var    Http  The HTTP client object to use in sending HTTP requests.
     * @since  1.0
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param   ?Registry  $options  Mediawiki options object.
     * @param   ?Http      $client   The HTTP client object.
     *
     * @since   1.0
     */
    public function __construct(Registry $options = null, Http $client = null)
    {
        $this->options = $options ?? new Registry();
        $this->client  = $client ?? new Http($this->options->toArray());
    }

    /**
     * Method to build and return a full request URL for the request.
     *
     * @param   string  $path  The query string of the request, starting with '?'. Values must be encoded.
     *
     * @return  string   The request URL.
     *
     * @since   1.0
     */
    protected function fetchUrl($path)
    {
        // Append the path with output format
        $path .= (strpos($path, '?') === false ? '?' : '&') . 'format=xml';

        $uri = new Uri($this->options->get('api.url') . '/api.php' . $path);

        if ($this->options->get('api.username', false)) {
            $uri->setUser($this->options->get('api.username'));
        }

        if ($this->options->get('api.password', false)) {
            $uri->setPass($this->options->get('api.password'));
        }

        return (string) $uri;
    }

    /**
     * Encode a value for use in the query string of a request.
     *
     * Every value is percent-encoded (RFC 3986), so characters such as `&`, `#`, `=` or `+` in a title
     * or search term are sent as part of the value instead of changing the request. The elements of an
     * array are encoded one by one and joined with `|`, the separator MediaWiki uses for lists.
     *
     * @param   array|string|int|float|bool  $value  The value or list of values.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function encodeParameter(array|string|int|float|bool $value): string
    {
        if (!\is_array($value)) {
            return rawurlencode((string) $value);
        }

        return implode('|', array_map(static fn ($element) => rawurlencode((string) $element), $value));
    }

    /**
     * Method to build request parameters from a string array.
     *
     * @param   array  $params  string array that contains the parameters
     *
     * @return  string   request parameter
     *
     * @since   1.0
     */
    public function buildParameter(array $params)
    {
        $path = '';

        foreach ($params as $param) {
            $path .= $param;

            if (next($params) == true) {
                $path .= '|';
            }
        }

        return $path;
    }

    /**
     * Method to validate response for errors
     *
     * @param   Response  $response  The response from the mediawiki server.
     *
     * @return  \SimpleXMLElement
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function validateResponse(Response $response)
    {
        $xml = simplexml_load_string((string) $response->getBody());

        if (isset($xml->warnings)) {
            throw new \DomainException($xml->warnings->info);
        }

        if (isset($xml->error)) {
            throw new \DomainException($xml->error['info']);
        }

        return $xml;
    }
}
