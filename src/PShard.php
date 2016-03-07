<?php
namespace PShard;

/**
 * Class for sharding data between PDO instances
 * using scheme with N virtual and M real shards.
 */
class PShard
{

    /**
     * Connection to database, containing shards map
     *
     * @var \PDO
     */
    protected $mapperConnection = null;

    /**
     * Shards settings
     *
     * @var array
     */
    protected $shardsMap = null;

    /**
     * Decimal representations of keys
     */
    protected $decKeys = [];

    /**
     * Connections' pool
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Constructor
     *
     * Connection to database, containing shards map.
     * @param \PDO $mapperConnection
     */
    public function __construct(\PDO $mapperConnection)
    {
        $this->mapperConnection = $mapperConnection;
    }

    /**
     * Returns connection resource for given key
     *
     * @param  string            $key
     * @return \PDO
     */
    public function getConnectionForKey($key = '')
    {
        $virtual = $this->getVirtualShard($key);
        $real    = $this->getRealShard($virtual);

        return $this->getShardConnection($real);
    }

    /**
     * Returns connection resource for given shardId and shardOptions
     *
     * @param  array $shardOptions
     * @return \PDO
     */
    protected function getShardConnection(array $shardOptions)
    {
        $shardId = $shardOptions['id'];
        if (!isset($this->connections[$shardId])) {
            $this->connections[$shardId] = new \PDO(
                $shardOptions['dsn'],
                (isset($shardOptions['username'])
                    ? $shardOptions['username']
                    : null),
                (isset($shardOptions['password'])
                    ? $shardOptions['password']
                    : null),
                (isset($shardOptions['options'])
                    ? $shardOptions['options']
                    : null)
            );
        }

        return $this->connections[$shardId];
    }

    /**
     * Returns shard data for given key.
     * Returned array structure is like below:
     * [
     *    'real'      => 'X',
     *    'virtual'   => 'Y',
     * ]
     *
     * @param  string $key
     * @return string
     */
    protected function getVirtualShard($key = '')
    {
        $key     = ($this->getDecKey($key) % count($this->getShardsMap()['virtual']));
        $virtual = $this->getShardsMap()['virtual'];

        return array_keys($virtual)[$key];
    }

    /**
     * Returns real shard address for virtual shard id
     *
     * @param  string $virtual
     * @return array
     */
    protected function getRealShard($virtual)
    {
        $shard = $this->getShardsMap()['virtual'][$virtual];

        return $this->getShardsMap()['real'][$shard['real']];
    }

    /**
     * Returns decimal representation of key
     *
     * @param  string  $key
     * @return integer
     */
    protected function getDecKey($key = '')
    {
        if (!isset($this->decKeys[$key])) {
            $this->decKeys[$key] = sha1($key);
        }

        return $this->decKeys[$key];
    }

    /**
     * Returns shards map
     *
     * @return array|null
     */
    protected function getShardsMap()
    {
        if ($this->shardsMap === null) {
            $this->shardsMap = [
                'real'    => [],
                'virtual' => [],
            ];

            /**
             * Table contains real shards options (dsn, username, password, options<.json>)
             */
            $statement = $this->mapperConnection->prepare('SELECT * FROM real_shard');
            $statement->execute();
            while ($item = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $item['options'] = json_decode($item['options'], true);
                $this->shardsMap['real'][$item['name']] = $item;
            }

            /**
             * Table contains 'virtual => real' shards mapping
             */
            $statement = $this->mapperConnection->prepare('SELECT * FROM virtual_shard');
            $statement->execute();
            while ($item = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $this->shardsMap['virtual'][$item['virtual']] = $item;
            }
        }

        return $this->shardsMap;
    }

}
