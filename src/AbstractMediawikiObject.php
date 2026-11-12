<?php

/**
 * Part of the Joomla Framework Mediawiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki;

use Joomla\Http\Response;
use Joomla\Mediawiki\Exception\ApiErrorException;
use Joomla\Mediawiki\Exception\ApiWarningException;
use Joomla\Mediawiki\Exception\UnexpectedResponseException;
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

        if ($this->options->get('api.username', false)) {
            $uri->setUser($this->options->get('api.username'));
        }

        if ($this->options->get('api.password', false)) {
            $uri->setPass($this->options->get('api.password'));
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
     * The body is parsed without network access and without emitting PHP warnings. All exceptions
     * thrown extend `\DomainException`.
     *
     * @param   Response  $response  The response from the mediawiki server.
     *
     * @return  \SimpleXMLElement
     *
     * @since   1.0
     * @throws  UnexpectedResponseException  When the body is not valid XML or the HTTP status is 400 or higher.
     * @throws  ApiErrorException            When the API reports an error.
     * @throws  ApiWarningException          When the API reports warnings and `api.throwOnWarnings` is enabled (default).
     */
    public function validateResponse(Response $response)
    {
        $status = $response->getStatusCode();

        $useInternalErrors = libxml_use_internal_errors(true);
        $xml               = simplexml_load_string((string) $response->getBody(), \SimpleXMLElement::class, \LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($xml === false) {
            throw new UnexpectedResponseException(
                \sprintf('The MediaWiki API returned a response that is not valid XML (HTTP status %d).', $status),
                $status
            );
        }

        if (isset($xml->error)) {
            throw new ApiErrorException((string) $xml->error['code'], (string) $xml->error['info']);
        }

        if ($status >= 400) {
            throw new UnexpectedResponseException(\sprintf('The MediaWiki API returned HTTP status %d.', $status), $status);
        }

        if (isset($xml->warnings) && $this->options->get('api.throwOnWarnings', true)) {
            $warnings = [];

            foreach ($xml->warnings->children() as $module => $warning) {
                $warnings[] = $module . ': ' . trim((string) $warning);
            }

            throw new ApiWarningException($warnings);
        }

        return $xml;
    }
}
