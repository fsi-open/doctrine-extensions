<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

class Entity implements KeymakerInterface
{
    /**
     * {@inheritDoc}
     */
    public function createKey($object, $property, $originalName)
    {
        $namespaceChunks = explode('\\', get_class($object));
        $name = array_pop($namespaceChunks);

        if (count($namespaceChunks)) {
            $matches = preg_grep('/.+Bundle$/', $namespaceChunks);
            if (count($matches)) {
                $tmp = array_shift($matches);
                $tmp = preg_replace('/(.+)Bundle$/', '$1', $tmp);
                $name = $tmp . '/' . $name;
            }
        }

        $hash = md5(uniqid());
        $hash = array(substr($hash, 0, 2), substr($hash, 2));
        $hash = implode('/', $hash);

        $name .= '/' . $property . '/' . $hash . '/' . basename($originalName);

        return '/' . $name;
    }
}
