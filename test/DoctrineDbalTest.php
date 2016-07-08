<?php
namespace Sample;

/**
 * Doctrine DBAL の使用方法.
 */
class DoctrineDbalTest extends \PHPUnit_Framework_TestCase
{
    private $conn = null;

    public function setUp()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = ['url' => 'mysql://root:pass@mysql/global'];
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        $this->conn->executeQuery('TRUNCATE items');
        $this->conn->insert('items', ['name' => 'item1']);
        $this->conn->insert('items', ['name' => 'item2']);
        $this->conn->insert('items', ['name' => 'item3']);
    }

    /**
     * Connection オブジェクトのチェック.
     * http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
     */
    public function testGetConnection()
    {
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $this->conn);
    }

    /**
     * PDO style query の動作確認.
     * http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html
     */
    public function testSimpleQuery()
    {
        $sql = "SELECT * FROM items";
        $items = $this->conn->fetchAll($sql);

        $this->assertEquals(3, count($items));
    }

    /**
     * SQL Query Builder の動作確認.
     * http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html
     */
    public function testQueryBuilder()
    {
        $queryBuilder = $this->conn->createQueryBuilder();
        $query = $queryBuilder
          ->select('*')
          ->from('items')
          ->where('name = ?')
          ->setParameter(0, 'item1');
        $stmt = $query->execute();
        $items = $stmt->fetchAll();

        $this->assertEquals(1, count($items));
        $this->assertEquals('item1', $items[0]['name']);
    }

    /**
     * Schema-Manager の動作確認.
     * http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-manager.html
     */
    public function testSchemaManager()
    {
        // Schema-Manager
        $schemaManager = $this->conn->getSchemaManager();

        $databases = $schemaManager->listDatabases();
        //var_dump($databases);
        $this->assertTrue(in_array('global', $databases));
        $this->assertTrue(in_array('shard1', $databases));
        $this->assertTrue(in_array('shard2', $databases));

        $tables = $schemaManager->listTables();
        //var_dump($tables);
        $this->assertEquals(2, count($tables));
        $this->assertInstanceOf('Doctrine\DBAL\Schema\Table', $tables[0]);
        $this->assertEquals('items', $tables[0]->getName());

        $table = $schemaManager->listTableDetails('items');
        //var_dump($table);
        $this->assertInstanceOf('Doctrine\DBAL\Schema\Table', $table);
        $this->assertEquals('items', $table->getName());

        $columns = $schemaManager->listTableColumns('items');
        //var_dump($columns);
        $this->assertEquals(2, count($columns));
        $this->assertInstanceOf('Doctrine\DBAL\Schema\Column', $columns['id']);
        $this->assertEquals('id', $columns['id']->getName());
        $this->assertInstanceOf('Doctrine\DBAL\Types\IntegerType', $columns['id']->getType());

        $indexes = $schemaManager->listTableIndexes('items');
        //var_dump($indexes);
        $this->assertInstanceOf('Doctrine\DBAL\Schema\Index', $indexes['primary']);
        $this->assertEquals(['id'], $indexes['primary']->getColumns());
    }

    /**
     * Caching の動作確認
     * http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/caching.html
     * 参考: http://qiita.com/imunew/items/050a20c52f8ce5937533
     */
    public function testChache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        // デフォルトキャッシュ設定
        $config = $this->conn->getConfiguration();
        $config->setResultCacheImpl($cache);

        // キャッシュプロファイル
        $fileCache = new \Doctrine\Common\Cache\FilesystemCache('/tmp/cache');
        $cacheProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(0, 'cache-key', $fileCache);

        $query = 'SELECT * FROM items';
        $stmt = $this->conn->executeQuery($query, [], [], $cacheProfile);
        $items1 = $stmt->fetchAll();
        $stmt->closeCursor();
        $this->assertEquals(3, count($items1));

        // items を削除して再度クエリ実行. キャッシュから読み込まれるので、以前の値を取得する.
        $this->conn->executeQuery('TRUNCATE items');
        $query = 'SELECT * FROM items';
        $stmt = $this->conn->executeQuery($query, [], [], $cacheProfile);
        $items2 = $stmt->fetchAll();
        $this->assertEquals(3, count($items2));
    }
}
