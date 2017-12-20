<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

use Doctrine\Common\Util\ClassUtils;

class TransliterateEntity implements KeymakerInterface
{
    const DEFAULT_PATTERN = '/{fqcn}/{property}/{id}/{original_name}';

    /**
     * {@inheritdoc}
     */
    public function createKey(
        $object,
        string $property,
        string $id,
        string $originalName,
        ?string $pattern = null
    ): string {
        if (is_null($pattern)) {
            $pattern = self::DEFAULT_PATTERN;
        }

        return preg_replace(
            [
                '/\{fqcn\}/',
                '/\{property\}/',
                '/\{id\}/',
                '/\{original_name\}/',
            ],
            [
                preg_replace('/\\\\/', '', ClassUtils::getClass($object)),
                $property,
                $id,
                $this->cleanFileName($originalName),
            ],
            $pattern
        );
    }

    private function cleanFileName(string $originalName): string
    {
        $transliteratedName = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; Lower()',
            $originalName
        );

        return trim(preg_replace(
            '/[-\s]+/', '-',
            preg_replace('/[^\da-z.\-\s]+/', '', $transliteratedName)
        ));
    }
}
