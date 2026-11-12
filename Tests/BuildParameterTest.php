<?php

/**
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Mediawiki\Tests;

use Joomla\Mediawiki\Http;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/stubs/AbstractMediawikiObjectMock.php';

/**
 * Tests for AbstractMediawikiObject::buildParameter().
 */
class BuildParameterTest extends TestCase
{
    /**
     * Lists and the parameter value they must give.
     *
     * @return  array
     */
    public static function lists(): array
    {
        return [
            'namespace 0 in the middle' => [['A', '0', 'B'], 'A|0|B'],
            'integer namespaces'        => [[0, 1, 0], '0|1|0'],
            'empty element'             => [['A', '', 'B'], 'A||B'],
            'falsy last element'        => [['A', '0'], 'A|0'],
            'single element'            => [['A'], 'A'],
            'empty list'                => [[], ''],
            'string keys'               => [['x' => 'A', 'y' => 'B'], 'A|B'],
        ];
    }

    /**
     * Every element is separated by '|', whatever its value.
     *
     * @param   array   $params    The list.
     * @param   string  $expected  The parameter value.
     *
     * @return  void
     */
    #[DataProvider('lists')]
    public function testBuildParameter(array $params, string $expected): void
    {
        $object = new AbstractMediawikiObjectMock(new Registry(), $this->createMock(Http::class));

        $this->assertSame($expected, $object->buildParameter($params));
    }
}
