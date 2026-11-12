<?php

/**
 * Part of the Joomla Framework MediaWiki Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki;

/**
 * MediaWiki API Sites class.
 *
 * @since  1.0
 */
class Sites extends AbstractMediawikiObject
{
    /**
     * Method to get site information.
     *
     * @param   ?array   $siprop            The sysinfo properties to get.
     * @param   string   $sifilteriw        Only local or only non local entries to return.
     * @param   boolean  $sishowalldb       List all database servers.
     * @param   boolean  $sinumberingroup   List the number of users in usergroups.
     * @param   ?array   $siinlanguagecode  Language code for localized languages.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function getSiteInfo(
        array $siprop = null,
        $sifilteriw = null,
        $sishowalldb = false,
        $sinumberingroup = false,
        array $siinlanguagecode = null
    ) {
        // Build the request.
        $path = '?action=query&meta=siteinfo';

        if (isset($siprop)) {
            $path .= '&siprop=' . $this->encodeParameter($siprop);
        }

        if (isset($sifilteriw)) {
            $path .= '&sifilteriw=' . $this->encodeParameter($sifilteriw);
        }

        if ($sishowalldb) {
            $path .= '&sishowalldb=';
        }

        if ($sinumberingroup) {
            $path .= '&sinumberingroup=';
        }

        if (isset($siinlanguagecode)) {
            $path .= '&siinlanguagecode=' . $this->encodeParameter($siinlanguagecode);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }

    /**
     * Method to get events from logs.
     *
     * @param   ?array   $leprop    List of properties to get.
     * @param   string   $letype    Filter log actions to only this type.
     * @param   string   $leaction  Filter log actions to only this type.
     * @param   string   $letitle   Filter entries to those related to a page.
     * @param   string   $leprefix  Filter entries that start with this prefix.
     * @param   string   $letag     Filter entries with tag.
     * @param   string   $leuser    Filter entries made by the given user.
     * @param   string   $lestart   Starting timestamp.
     * @param   string   $leend     Ending timestamp.
     * @param   string   $ledir     Direction of enumeration.
     * @param   integer  $lelimit   Event limit to return.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function getEvents(
        array $leprop = null,
        $letype = null,
        $leaction = null,
        $letitle = null,
        $leprefix = null,
        $letag = null,
        $leuser = null,
        $lestart = null,
        $leend = null,
        $ledir = null,
        $lelimit = null
    ) {
        // Build the request
        $path = '?action=query&list=logevents';

        if (isset($leprop)) {
            $path .= '&leprop=' . $this->encodeParameter($leprop);
        }

        if (isset($letype)) {
            $path .= '&letype=' . $this->encodeParameter($letype);
        }

        if (isset($leaction)) {
            $path .= '&leaction=' . $this->encodeParameter($leaction);
        }

        if (isset($letitle)) {
            $path .= '&letitle=' . $this->encodeParameter($letitle);
        }

        if (isset($leprefix)) {
            $path .= '&leprefix=' . $this->encodeParameter($leprefix);
        }

        if (isset($letag)) {
            $path .= '&letag=' . $this->encodeParameter($letag);
        }

        if (isset($leuser)) {
            $path .= '&leuser=' . $this->encodeParameter($leuser);
        }

        if (isset($lestart)) {
            $path .= '&lestart=' . $this->encodeParameter($lestart);
        }

        if (isset($leend)) {
            $path .= '&leend=' . $this->encodeParameter($leend);
        }

        if (isset($ledir)) {
            $path .= '&ledir=' . $this->encodeParameter($ledir);
        }

        if (isset($lelimit)) {
            $path .= '&lelimit=' . $this->encodeParameter($lelimit);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }

    /**
     * Method to get recent changes on a site.
     *
     * @param   string  $rcstart        Starting timestamp.
     * @param   string  $rcend          Ending timestamp.
     * @param   string  $rcdir          Direction of enumeration.
     * @param   ?array  $rcnamespace    Filter changes to only this namespace(s).
     * @param   string  $rcuser         Filter changes by this user.
     * @param   string  $rcexcludeuser  Filter changes to exclude changes by this user.
     * @param   string  $rctag          Filter changes by this tag.
     * @param   ?array  $rcprop         Filter log actions to only this type.
     * @param   ?array  $rctoken        Which token to obtain for each change.
     * @param   ?array  $rcshow         Filter changes by this criteria.
     * @param   string  $rclimit        Changes limit to return.
     * @param   string  $rctype         Filter event by type of changes.
     * @param   string  $rctoponly      Filter changes which are latest revision.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function getRecentChanges(
        $rcstart = null,
        $rcend = null,
        $rcdir = null,
        array $rcnamespace = null,
        $rcuser = null,
        $rcexcludeuser = null,
        $rctag = null,
        array $rcprop = null,
        array $rctoken = null,
        array $rcshow = null,
        $rclimit = null,
        $rctype = null,
        $rctoponly = null
    ) {
        // Build the request.
        $path = '?action=query&list=recentchanges';

        if (isset($rcstart)) {
            $path .= '&rcstart=' . $this->encodeParameter($rcstart);
        }

        if (isset($rcend)) {
            $path .= '&rcend=' . $this->encodeParameter($rcend);
        }

        if (isset($rcdir)) {
            $path .= '&rcdir=' . $this->encodeParameter($rcdir);
        }

        if (isset($rcnamespace)) {
            $path .= '&rcnamespaces=' . $this->encodeParameter($rcnamespace);
        }

        if (isset($rcuser)) {
            $path .= '&rcuser=' . $this->encodeParameter($rcuser);
        }

        if (isset($rcexcludeuser)) {
            $path .= '&rcexcludeuser=' . $this->encodeParameter($rcexcludeuser);
        }

        if (isset($rctag)) {
            $path .= '&rctag=' . $this->encodeParameter($rctag);
        }

        if (isset($rcprop)) {
            $path .= '&rcprop=' . $this->encodeParameter($rcprop);
        }

        if (isset($rctoken)) {
            $path .= '&rctoken=' . $this->encodeParameter($rctoken);
        }

        if (isset($rcshow)) {
            $path .= '&rcshow=' . $this->encodeParameter($rcshow);
        }

        if (isset($rclimit)) {
            $path .= '&rclimit=' . $this->encodeParameter($rclimit);
        }

        if (isset($rctype)) {
            $path .= '&rctype=' . $this->encodeParameter($rctype);
        }

        if (isset($rctoponly)) {
            $path .= '&rctoponly=' . $this->encodeParameter($rctoponly);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }

    /**
     * Method to get protected titles on a site.
     *
     * @param   ?array   $ptnamespace  Only list titles in this namespace.
     * @param   ?array   $ptlevel      Only list titles with these protection level.
     * @param   integer  $ptlimit      Limit of pages to return.
     * @param   string   $ptdir        Direction of enumeration.
     * @param   string   $ptstart      Starting timestamp.
     * @param   string   $ptend        Ending timestamp.
     * @param   ?array   $ptprop       List of properties to get.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function getProtectedTitles(
        array $ptnamespace = null,
        array $ptlevel = null,
        $ptlimit = null,
        $ptdir = null,
        $ptstart = null,
        $ptend = null,
        array $ptprop = null
    ) {
        // Build the request.
        $path = '?action=query&list=protectedtitles';

        if (isset($ptnamespace)) {
            $path .= '&ptnamespace=' . $this->encodeParameter($ptnamespace);
        }

        if (isset($ptlevel)) {
            $path .= '&ptlevel=' . $this->encodeParameter($ptlevel);
        }

        if (isset($ptlimit)) {
            $path .= '&ptlimit=' . $this->encodeParameter($ptlimit);
        }

        if (isset($ptdir)) {
            $path .= '&ptdir=' . $this->encodeParameter($ptdir);
        }

        if (isset($ptstart)) {
            $path .= '&ptstart=' . $this->encodeParameter($ptstart);
        }

        if (isset($ptend)) {
            $path .= '&ptend=' . $this->encodeParameter($ptend);
        }

        if (isset($ptprop)) {
            $path .= '&ptprop=' . $this->encodeParameter($ptprop);
        }

        // Send the request.
        $response = $this->client->get($this->fetchUrl($path));

        return $this->validateResponse($response);
    }
}
