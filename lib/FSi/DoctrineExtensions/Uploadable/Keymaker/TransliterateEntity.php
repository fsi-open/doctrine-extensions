<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

class TransliterateEntity implements KeymakerInterface
{
    const DEFAULT_PATTERN = '/{fqcn}/{property}/{id}/{original_name}';

    /**
     * {@inheritdoc}
     */
    public function createKey($object, $property, $id, $originalName, $pattern = null)
    {
        if (is_null($pattern)) {
            $pattern = self::DEFAULT_PATTERN;
        }

        return preg_replace(
            array(
                '/\{fqcn\}/',
                '/\{property\}/',
                '/\{id\}/',
                '/\{original_name\}/',
            ),
            array(
                preg_replace('/\\\\/', '', get_class($object)),
                $property,
                $id,
                $this->cleanFileName($originalName),
            ),
            $pattern
        );
    }

    private function cleanFileName($originalName)
    {
        $transliteratedName = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; Lower()',
            $originalName
        );

        $strippedName = preg_replace('/[^\da-z.\-\s]+/', '', $transliteratedName);

        return trim(preg_replace('/[-\s]+/', '-', $strippedName));
    }
}
