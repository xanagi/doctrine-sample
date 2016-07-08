<?php
namespace Sample;

/**
 * Doctrine DBAL Sharding の使用方法.
 */
class DoctrineDbalShardingTest extends \PHPUnit_Framework_TestCase
{
    private $conn = null;

    public function setUp()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $this->connGlobal = \Doctrine\DBAL\DriverManager::getConnection(['url' => 'mysql://root:pass@mysql/global'], $config);
        $this->connShard1 = \Doctrine\DBAL\DriverManager::getConnection(['url' => 'mysql://root:pass@mysql/shard1'], $config);
        $this->connShard2 = \Doctrine\DBAL\DriverManager::getConnection(['url' => 'mysql://root:pass@mysql/shard2'], $config);

        $this->conn = \Doctrine\DBAL\DriverManager::getConnection([
          'wrapperClass' => 'Doctrine\DBAL\Sharding\PoolingShardConnection',
          'driver' => 'pdo_mysql',
          'global' => ['user' => 'root', 'password' => 'pass', 'host' => 'mysql', 'dbname' => 'global'],
          'shards' => [
            ['id' => 1, 'user' => 'root', 'password' => 'pass', 'host' => 'mysql', 'dbname' => 'shard1'],
            ['id' => 2, 'user' => 'root', 'password' => 'pass', 'host' => 'mysql', 'dbname' => 'shard2'],
          ],
          'shardChoser' => 'Sample\SampleShardChoser'
        ]);
        $this->shardManager = new \Doctrine\DBAL\Sharding\PoolingShardManager($this->conn);

        $this->shardManager->selectShard(1);
        $this->conn->executeQuery('TRUNCATE users');
        $this->shardManager->selectShard(2);
        $this->conn->executeQuery('TRUNCATE users');
        for($i=1;$i<=6;$i++) {
            $this->shardManager->selectShard($i);
            $this->conn->insert('users', ['id' => $i, 'name' => "user{$i}"]);
        }
    }

    /**
     * GUID による一意な ID の取得.
     */
    public function testGetGuid()
    {
        $guid1 = $this->conn->fetchColumn('SELECT ' . $this->conn->getDatabasePlatform()->getGuidExpression());
        $guid2 = $this->conn->fetchColumn('SELECT ' . $this->conn->getDatabasePlatform()->getGuidExpression());
        $this->assertNotEquals($guid1, $guid2);
    }

    /**
     * Table Generator による一意な ID の取得.
     */
    public function testGetTableGeneratorValue()
    {
        $tableGenerator = new \Doctrine\DBAL\Id\TableGenerator($this->conn, "sequences");
        $id1 = $tableGenerator->nextValue("sequence_name1");
        $id2 = $tableGenerator->nextValue("sequence_name1");
        $this->assertEquals($id1 + 1, $id2);
    }

    /**
     * Global データベースを選択.
     */
    public function testSelectGlobal()
    {
        $this->shardManager->selectGlobal();
        //var_dump($this->conn);
    }

    /**
     * Shard データベースを選択.
     */
    public function testSelectShard()
    {
        $this->shardManager->selectShard(1);
        $this->assertEquals(1, $this->shardManager->getCurrentDistributionValue());
        $sql = "SELECT * FROM users";
        $users = $this->conn->fetchAll($sql);
        //var_dump($users);
        $this->assertEquals(3, count($users));
        $this->assertEquals([1, 3, 5], array_map(function($user){return $user['id'];}, $users));

        $this->shardManager->selectShard(102);
        $this->assertEquals(102, $this->shardManager->getCurrentDistributionValue());
        $sql = "SELECT * FROM users";
        $users = $this->conn->fetchAll($sql);
        //var_dump($users);
        $this->assertEquals(3, count($users));
        $this->assertEquals([2, 4, 6], array_map(function($user){return $user['id'];}, $users));
    }

    /**
     *  "fan-out" query API
     */
    public function testQueryAll()
    {
        $sql = 'SELECT * FROM users';
        $users = $this->shardManager->queryAll($sql, [], []);
        //var_dump($users);
        $this->assertEquals(6, count($users));
        $this->assertEquals([1, 3, 5, 2, 4, 6], array_map(function($user){return $user['id'];}, $users));
    }
}
