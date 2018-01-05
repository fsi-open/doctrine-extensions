<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation\Uploadable;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     */
    public $name = 'chuck testa';

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     * @Uploadable(targetField="file", filesystem="one")
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     * @Uploadable(targetField="file2", filesystem="two")
     */
    protected $file2Key;

    /**
     * @var mixed
     */
    protected $file2;

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

    public function setFile2Key(?string $key)
    {
        $this->file2Key = $key;
    }

    public function getFile2Key(): ?string
    {
        return $this->file2Key;
    }

    public function setFile2($file): void
    {
        if (!empty($file)) {
            $this->file2 = $file;
        }
    }

    public function getFile2()
    {
        return $this->file2;
    }

    public function deleteFile2(): void
    {
        $this->file2 = null;
    }
}
