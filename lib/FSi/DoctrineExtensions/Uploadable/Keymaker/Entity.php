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

        $name = '/'. $baseName . '/' . $property . '/' . $tmpHash . '/' . basename($originalName);

        return $this->shortenKey($name, $keyLength);
    }

    /**
     * Shortening key when original filename is too long.
     *
     * It make that name from 'originalname(.longextension)' to minimum form as 'o(.lon)'.
     * If there is still not enough space, exception is thrown.
     *
     * @param string $key
     * @param integer $keyLength
     * @return string
     * @throws \FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException
     */
    private function shortenKey($key, $keyLength)
    {
        $length = mb_strlen($key);
        // $diff says how many characters must be removed.
        $diff = $length - $keyLength;

        if (0 > $diff) {
            return $key;
        }

        $tmp = explode('/', $key);
        $basename = array_pop($tmp);
        $dirname = implode('/', $tmp) . '/';

        $tmp = explode('.', $basename);
        $extension = (1 < count($tmp)) ? array_pop($tmp) : null;
        $filename = implode('.', $tmp);

        $filenameLength = mb_strlen($filename);
        $newLength = $filenameLength - $diff - 1;
        if ($newLength < 1) {
            $newLength = 1;
            // If extension is set, we try to shorten extension.
            if ($extension) {
                $newExtensionLength =  $diff * -1;
                if ($newExtensionLength < 3) {
                    $newExtensionLength = 3;
                }
                $extension = mb_substr($extension, 0, $newExtensionLength);
            }
        }
        $filename = mb_substr($filename, 0, $newLength);

        $key = $dirname . $filename;
        if ($extension) {
            $key .= '.' . $extension;
        }

        if (mb_strlen($key) > $keyLength) {
            throw new RuntimeException(sprintf("Not enough space for creating key (there must be minimum \"%d\" characters space.", mb_strlen($key)));
        }

        return $key;
    }
}
