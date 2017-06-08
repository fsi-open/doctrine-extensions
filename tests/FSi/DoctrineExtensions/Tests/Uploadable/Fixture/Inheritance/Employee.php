<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\File;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as FSi;
use SplFileInfo;

/**
 * @ORM\Entity
 */
class Employee extends Person
{
    /**
     * @ORM\Column(nullable=true)
     * @FSi\Uploadable(targetField="file")
     */
    private $filePath;

    /**
     * @var File|SplFileInfo
     */
    private $file;

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }
}
