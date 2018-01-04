<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

interface KeymakerInterface
{
    /**
     * @param object $object
     * @param string $property
     * @param int|string $id
     * @param string $originalName
     * @param string|null $pattern
     * @return string
     */
    public function createKey(
        $object,
        string $property,
        $id,
        string $originalName,
        ?string $pattern = null
    ): string;
}
