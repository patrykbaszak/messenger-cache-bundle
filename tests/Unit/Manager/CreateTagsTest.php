<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Manager;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Provider\CacheTagProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @group unit */
class CreateTagsTest extends KernelTestCase
{
    private MessengerCacheManagerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = self::getContainer()->get('messenger_cache.manager');
    }

    private function createTags(?string $ownerIdentifier, array $tags, bool $useOwnerIdentifierForTags, ?string ...$groups): array
    {
        $class = new \ReflectionClass($this->manager);
        $method = $class->getMethod(__FUNCTION__);
        $method->setAccessible(true);

        return $method->invokeArgs($this->manager, func_get_args());
    }

    /** @test */
    public function shouldReturnEmptyArray(): void
    {
        $this->assertEquals([], $this->createTags(null, [], false, null));
    }

    /** @test */
    public function shouldReturnGivenTags(): void
    {
        $this->assertEquals(['tag1', 'tag2'], $this->createTags(null, ['tag1', 'tag2'], false, null));
    }

    /** @test */
    public function shouldReturnOwnerIdentifier(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
            ],
            $this->createTags('owner', [], false, null)
        );
    }

    /** @test */
    public function shouldReturnOwnerIdentifierAndGivenTags(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                'tag1',
                'tag2',
            ],
            $this->createTags('owner', ['tag1', 'tag2'], false, null)
        );
    }

    /** @test */
    public function shouldReturnOwnerIdentifierAndGroup(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                (new CacheTagProvider())->createGroupTag('group', 'owner'),
            ],
            $this->createTags('owner', [], false, 'group')
        );
    }

    /** @test */
    public function shouldReturnOwnerIdentifierAndGivenTagsAndGroup(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                (new CacheTagProvider())->createGroupTag('group', 'owner'),
                'tag1',
                'tag2',
            ],
            $this->createTags('owner', ['tag1', 'tag2'], false, 'group')
        );
    }

    /** @test */
    public function shouldReturnOwnerIdentifierAndGivenTagsAndGroups(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                (new CacheTagProvider())->createGroupTag('group1', 'owner'),
                (new CacheTagProvider())->createGroupTag('group2', 'owner'),
                'tag1',
                'tag2',
            ],
            $this->createTags('owner', ['tag1', 'tag2'], false, 'group1', 'group2')
        );
    }

    /** @test */
    public function shouldReturnOwnerIdentifierAndGivenTagsAndGroupsAndUseOwnerIdentifierForTags(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                (new CacheTagProvider())->createGroupTag('group1', 'owner'),
                (new CacheTagProvider())->createGroupTag('group2', 'owner'),
                (new CacheTagProvider())->createGroupTag('tag1', 'owner'),
                (new CacheTagProvider())->createGroupTag('tag2', 'owner'),
            ],
            $this->createTags('owner', ['tag1', 'tag2'], true, 'group1', 'group2')
        );
    }

    /** @test */
    public function shouldReturnTagsWithOwnerIdentifier(): void
    {
        $this->assertEquals(
            [
                (new CacheTagProvider())->createOwnerTag('owner'),
                (new CacheTagProvider())->createGroupTag('tag1', 'owner'),
                (new CacheTagProvider())->createGroupTag('tag2', 'owner'),
            ],
            $this->createTags('owner', ['tag1', 'tag2'], true)
        );
    }
}
