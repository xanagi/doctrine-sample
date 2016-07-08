<?php
namespace Sample;

use Doctrine\DBAL\Sharding\ShardChoser\ShardChoser;
use Doctrine\DBAL\Sharding\PoolingShardConnection;

/**
 * 奇数(1), 偶数(2) でシャードをふり分ける ShardChooser
 */
class SampleShardChoser implements ShardChoser
{
    public function pickShard($distributionValue, PoolingShardConnection $conn)
    {
        $mod = $distributionValue % 2;
        return ($mod == 1) ? 1 : 2;
    }
}
