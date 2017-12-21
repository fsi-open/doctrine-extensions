<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Uploadable\Keymaker\TransliterateEntity;
use PHPUnit\Framework\TestCase;

class TransliterateKeymakerTest extends TestCase
{
    const PROPERTY = 'property';
    const ID = 1;

    const ORIGINAL_NAME_SPECIAL = '"[[<###$$or~ i+ginal,?;: \\\   /Na%*&^m!e#$!|>]}.txt"';
    const PARSED_NAME_SPECIAL = 'or-iginal-name.txt';

    const ORIGINAL_NAME_PL = '"ołrigiźćnaąślNaóęńme.txt"';
    const PARSED_NAME_PL = 'olrigizcnaaslnaoenme.txt';

    const ORIGINAL_NAME_GER = 'örigiünälNÄÜméÖß.txt';
    const PARSED_NAME_GER = 'origiunalnaumeoss.txt';

    public function testCreation()
    {
        $keyMaker = new TransliterateEntity();
        $this->assertTrue($keyMaker instanceof KeymakerInterface);
    }

    /**
     * @dataProvider inputsSpecial
     */
    public function testSpecialSignKeyGeneration($pattern, $expected)
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_SPECIAL);
    }

    /**
     * @dataProvider inputsPolish
     */
    public function testPolishKeyGeneration($pattern, $expected)
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_PL);
    }

    /**
     * @dataProvider inputsGerman
     */
    public function testGermanKeyGeneration($pattern, $expected)
    {
        $this->assertKeyGeneration($pattern, $expected, self::ORIGINAL_NAME_GER);
    }

    /**
     * @return array
     */
    public static function inputsSpecial()
    {
        return self::getInputs(self::PARSED_NAME_SPECIAL);
    }

    /**
     * @return array
     */
    public static function inputsPolish()
    {
        return self::getInputs(self::PARSED_NAME_PL);
    }

    /**
     * @return array
     */
    public static function inputsGerman()
    {
        return self::getInputs(self::PARSED_NAME_GER);
    }

    private function assertKeyGeneration($pattern, $expected, $original)
    {
        $keyMaker = new TransliterateEntity();
        $user = new User();

        $this->assertEquals(
            $expected,
            $keyMaker->createKey(
                $user,
                self::PROPERTY,
                self::ID,
                $original,
                $pattern
            )
        );
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getInputs($name)
    {
        $testSets = [
            [
                '{fqcn}/{id}/constant',
                'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::ID
                . '/constant'
            ],
            [
                null,
                '/FSiDoctrineExtensionsTestsUploadableFixtureUser/'
                . self::PROPERTY . '/' . self::ID . '/%s'
            ],
            [
                '{fqcn}/{property}/{wrong_tag}/{id}/{original_name}',
                'FSiDoctrineExtensionsTestsUploadableFixtureUser/'
                . self::PROPERTY . '/{wrong_tag}/' . self::ID . '/%s'
            ]
        ];
        $inputs = [];
        foreach ($testSets as $set) {
            $set[1] = sprintf($set[1], $name);
            $inputs[] = $set;
        }
        return $inputs;
    }
}
