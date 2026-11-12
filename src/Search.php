<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki;

/**
 * MediaWiki API Search class.
 *
 * @since  1.0
 */
class Search extends AbstractMediawikiObject
{
    /**
     * Method to perform a full text search.
     *
     * @param   string   $srsearch     Search for all page titles (or content) that has this value.
     * @param   ?array   $srnamespace  The namespace(s) to enumerate.
     * @param   string   $srwhat       Search inside the text or titles.
     * @param   ?array   $srinfo       What metadata to return.
     * @param   ?array   $srprop       What properties to return.
     * @param   boolean  $srredirects  Include redirect pages in the search.
     * @param   integer  $sroffest     Use this value to continue paging.
     * @param   integer  $srlimit      How many total pages to return.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function search(
        $srsearch,
        array $srnamespace = null,
        $srwhat = null,
        array $srinfo = null,
        array $srprop = null,
        $srredirects = null,
        $sroffest = null,
        $srlimit = null
    ) {
        // Build the request.
        $path = '?action=query&list=search';

        $path .= '&srsearch=' . $this->encodeParameter($srsearch);

        if (isset($srnamespace)) {
            $path .= '&srnamespace=' . $this->encodeParameter($srnamespace);
        }

        if (isset($srwhat)) {
            $path .= '&srwhat=' . $this->encodeParameter($srwhat);
        }

        if (isset($srinfo)) {
            $path .= '&srinfo=' . $this->encodeParameter($srinfo);
        }

        if (isset($srprop)) {
            $path .= '&srprop=' . $this->encodeParameter($srprop);
        }

        if ($srredirects) {
            $path .= '&srredirects=';
        }

        if (isset($sroffest)) {
            $path .= '&sroffest=' . $this->encodeParameter($sroffest);
        }

        if (isset($srlimit)) {
            $path .= '&srlimit=' . $this->encodeParameter($srlimit);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }

    /**
     * Method to search the wiki using opensearch protocol.
     *
     * @param   string   $search     Search string.
     * @param   integer  $limit      Maximum amount of results to return.
     * @param   ?array   $namespace  Namespaces to search.
     * @param   string   $suggest    Do nothing if $wgEnableOpenSearchSuggest is false.
     * @param   string   $format     Output format.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function openSearch($search, $limit = null, array $namespace = null, $suggest = null, $format = null)
    {
        // Build the request.
        $path = '?action=query&list=search';

        $path .= '&search=' . $this->encodeParameter($search);

        if (isset($limit)) {
            $path .= '&limit=' . $this->encodeParameter($limit);
        }

        if (isset($namespace)) {
            $path .= '&namespace=' . $this->encodeParameter($namespace);
        }

        if (isset($suggest)) {
            $path .= '&suggest=' . $this->encodeParameter($suggest);
        }

        if (isset($format)) {
            $path .= '&format=' . $this->encodeParameter($format);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }
}
