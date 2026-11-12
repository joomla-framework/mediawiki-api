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
     * Method to build and return a full request URL for the request. When `api.username` is set,
     * this also sets the `Authorization` header (HTTP Basic, with `api.password`) in the `headers`
     * option of the HTTP client.
     *
     * @param   string  $path  URL to inflect
     *
     * @return  string   The request URL.
     *
     * @since   1.0
     */
    protected function fetchUrl($path)
    {
        // Append the path with output format
        $path .= '&format=xml';

        $uri = new Uri($this->options->get('api.url') . '/api.php' . $path);

        // HTTP credentials are sent as a header, never as part of the URL.
        if ($this->options->get('api.username', false)) {
            $headers = (array) $this->client->getOption('headers', []);

            $headers['Authorization'] = 'Basic ' . base64_encode(
                $this->options->get('api.username') . ':' . $this->options->get('api.password', '')
            );

            $this->client->setOption('headers', $headers);
        }

        return (string) $uri;
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
