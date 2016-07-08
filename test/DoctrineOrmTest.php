<?php
namespace Sample;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Sample\Item;

/**
 * Doctrine DBAL の使用方法.
 */
class DoctrineOrmTest extends \PHPUnit_Framework_TestCase
{
    private $entityManager = null;

    public function setUp()
    {
        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src"), $isDevMode);

        $conn = [
            'dbname' => 'global',
            'user' => 'root',
            'password' => 'pass',
            'host' => 'mysql',
            'driver' => 'pdo_mysql',
        ];
        $this->entityManager = EntityManager::create($conn, $config);
    }

    /**
     * 新規 Entity 作成.
     */
    public function testCreate()
    {
        $item = new Item();
        $item->setName('item1');

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $this->assertEquals(1, $item->getId());
        $item1 = $this->entityManager->find('Sample\Item', 1);
        $this->assertEquals('item1', $item1->getName());
    }

    /**
     * Entity 更新.
     */
    public function testUpdate()
    {
        $item = $this->entityManager->find('Sample\Item', 1);
        $item->setName('item1 renamed');
        $this->entityManager->flush();

        $item1 = $this->entityManager->find('Sample\Item', 1);
        $this->assertEquals('item1 renamed', $item1->getName());
    }

    /**
     * Entity 削除.
     */
    public function testDelete()
    {
        $item1 = $this->entityManager->find('Sample\Item', 1);
        $this->entityManager->remove($item1);
        $this->entityManager->flush();

        $item1 = $this->entityManager->find('Sample\Item', 1);
        $this->assertNull($item1);
    }

    public function testFindAll()
    {
        $item2 = new Item();
        $item2->setName('item2');
        $item3 = new Item();
        $item3->setName('item3');

        $this->entityManager->persist($item2);
        $this->entityManager->persist($item3);
        $this->entityManager->flush();

        $itemRepository = $this->entityManager->getRepository('Sample\Item');
        $items = $itemRepository->findAll();
        $this->assertEquals(2, count($items));
        $this->assertEquals('item2', $items[0]->getName());
        $this->assertEquals('item3', $items[1]->getName());
    }
}
