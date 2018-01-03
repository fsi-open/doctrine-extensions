<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\Mapping;

use FSi\DoctrineExtensions\Metadata\AbstractClassMetadata;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var array
     */
    protected $uploadableProperties = [];

    public function addUploadableProperty(
        string $property,
        string $targetField,
        ?string $filesystem = null,
        ?KeymakerInterface $keymaker = null,
        ?int $keyLength = null,
        ?string $keyPattern = null
    ) {
        $this->uploadableProperties[$property] = [
            'targetField' => $targetField,
            'filesystem' => $filesystem,
            'keymaker' => $keymaker,
            'keyLength' => $keyLength,
            'keyPattern' => $keyPattern,
        ];
    }

    public function hasUploadableProperties(): bool
    {
        return !empty($this->uploadableProperties);
    }

    /**
     * Returns array of all uploadable properties indexed by property.
     */
    public function getUploadableProperties(): array
    {
        return $this->uploadableProperties;
    }
}
