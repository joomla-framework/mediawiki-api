<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Mediawiki\Http;
use PHPUnit\Framework\TestCase;

/**
 * Tests how Http::post() sends boolean flags.
 */
class HttpPostDataTest extends TestCase
{
    /**
     * Send data through Http::post() and return what reached the transport.
     *
     * @param   mixed  $data  The data to post.
     *
     * @return  mixed
     */
    private function postAndCapture($data)
    {
        $captured  = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('request')
            ->willReturnCallback(
                function ($method, $uri, $sent) use (&$captured) {
                    $captured = $sent;

                    return new Response('data://text/plain,<api/>', 200);
                }
            );

        (new Http([], $transport))->post('https://wiki.example.org/w/api.php?action=block&format=xml', $data);

        return $captured;
    }

    /**
     * False and null flags are left out, true is sent as '1', other values are kept.
     *
     * @return  void
     */
    public function testBooleanFlagsAreNormalised(): void
    {
        $sent = $this->postAndCapture(
            [
                'user'      => 'Vandal',
                'hidename'  => false,
                'nocreate'  => false,
                'autoblock' => true,
                'reason'    => null,
                'section'   => 0,
                'text'      => '0',
                'summary'   => '',
            ]
        );

        $this->assertSame(
            [
                'user'      => 'Vandal',
                'autoblock' => '1',
                'section'   => 0,
                'text'      => '0',
                'summary'   => '',
            ],
            $sent
        );
        $this->assertSame('user=Vandal&autoblock=1&section=0&text=0&summary=', http_build_query($sent));
    }

    /**
     * Data that is not an array is passed through.
     *
     * @return  void
     */
    public function testStringDataIsUnchanged(): void
    {
        $this->assertSame('a=0', $this->postAndCapture('a=0'));
        $this->assertNull($this->postAndCapture(null));
    }
}
