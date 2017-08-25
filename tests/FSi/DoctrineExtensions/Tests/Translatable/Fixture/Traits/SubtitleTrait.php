<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture\Traits;

trait SubtitleTrait
{
    /**
     * @Translatable\Translatable(mappedBy="translations", targetField="subtitle")
     * @var string
     */
    private $subtitle;

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }
}
