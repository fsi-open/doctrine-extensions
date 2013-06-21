<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;

class Entity implements KeymakerInterface
{
    /**
     * {@inheritDoc}
     */
    public function createKey($object, $property, $originalName, $keyLength)
    {
        $keyLength = (int) $keyLength;
        if (1 > $keyLength) {
            throw new RuntimeException(sprintf("Key length must be greater than 0 (\"%s\" given)", $keyLength));
        }

        $namespaceChunks = explode('\\', get_class($object));
        $baseName = array_pop($namespaceChunks);

        if (count($namespaceChunks)) {
            $matches = preg_grep('/.+Bundle$/', $namespaceChunks);
            if (count($matches)) {
                $tmp = array_shift($matches);
                $tmp = preg_replace('/(.+)Bundle$/', '$1', $tmp);
                $baseName = $tmp . '/' . $baseName;
            }
        }

        $hash = md5(uniqid());
        $tmpHash = array(substr($hash, 0, 2), substr($hash, 2));
        $tmpHash = implode('/', $tmpHash);
        $fileName = basename($originalName);

        $rootName = '/'. $baseName . '/' . $property . '/' . $tmpHash . '/';
        $name = $rootName . $fileName;

        $length = mb_strlen($name);
        if ($length <= $keyLength) {
            return $name;
        }

        // Case when original filename is too long.
        // It make that name from 'originalname.longextension' to minimum form as 'o.lon'.
        // If there is still not enough space, exception is thrown.

        $parts = pathinfo($fileName);
        $filenameLength = mb_strlen($parts['filename']);
        // $diff says how much name must be shorten
        $diff = $filenameLength - ($length - $keyLength);
        $stripExtension = 0;
        if ($diff < 1) {
            // $stripExtension says how many characters needs to be cut off from extension.
            $stripExtension = $diff * -1 + 1;
            $diff = 1;
        }
        $parts['filename'] = substr($parts['filename'], 0, $diff);

        if (0 != $stripExtension) {
            // And now $stripExtension says how many digits we need to leave as they are.
            $stripExtension = mb_strlen($parts['extension'] - $stripExtension);
            if ($stripExtension < 3) {
                $stripExtension = 3;
            }
            $parts['extension'] = substr($parts['extension'], 0, $stripExtension);
        }

        $name = $rootName . $parts['filename'] . '.' . $parts['extension'];

        if (mb_strlen($name) > $keyLength) {
            throw new RuntimeException(sprintf("Not enough space for creating key (there must be minimum \"%d\" characters space.", mb_strlen($name)));
        }

        return $name;
    }
}
