<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml;

class Car
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name = 'chuck testa';

    /**
     * @var string
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFileKey(?string $key): void
    {
        $this->fileKey = $key;
    }

    public function getFileKey(): ?string
    {
        return $this->fileKey;
    }

    public function setFile($file)
    {
        if (!empty($file)) {
            $this->file = $file;
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function deleteFile(): void
    {
        $this->file = null;
    }
}
