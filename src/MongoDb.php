<?php


namespace Mryup\HyperfMongodb;


use Mryup\HyperfMongodb\Exception\MongoDBException;
use Mryup\HyperfMongodb\Pool\PoolFactory;
use Hyperf\Utils\Context;

/**
 * Class MongoDb
 * @package Hyperf\Mongodb
 */
class MongoDb
{
    /**
     * @var PoolFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $poolName = 'default';

    public function __construct(PoolFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setPool($poolName){
        $this->poolName = $poolName;
        return $this;
    }

    public function getPool(){
        return $this->poolName;
    }

    /**
     * 返回满足filer的全部数据
     *
     * @param string $namespace
     * @param array $filter
     * @param array $options
     * @param bool $isIdAuto
     * @return array
     * @throws MongoDBException
     */
    public function fetchAll(string $namespace, array $filter = [], array $options = [],bool $isIdAuto = true): array
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->executeQueryAll($namespace, $filter, $options,$isIdAuto);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 返回满足filer的第一行
     *
     * @param string $namespace
     * @param array $filter
     * @param array $options
     * @param bool $isIdAuto
     * @return array
     * @throws MongoDBException
     */
    public function findOne(string $namespace, array $filter = [], array $options = [],bool $isIdAuto = true): array
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            $options['limit'] = 1;
            $ret =  $collection->executeQueryAll($namespace, $filter, $options,$isIdAuto);
            return $ret[0]??[];
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 返回满足filer的分页数据
     *
     * @param string $namespace
     * @param int $limit
     * @param int $currentPage
     * @param array $filter
     * @param array $options
     * @param bool $isIdAuto
     * @return array
     * @throws MongoDBException
     */
    public function fetchPagination(string $namespace, int $limit, int $currentPage, array $filter = [], array $options = [],bool $isIdAuto = true): array
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->execQueryPagination($namespace, $limit, $currentPage, $filter, $options,$isIdAuto);
        } catch (\Exception  $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 批量插入
     * @param $namespace
     * @param array $data
     * @return bool|string
     * @throws MongoDBException
     */
    public function insertAll($namespace, array $data)
    {
        if (count($data) == count($data, 1)) {
            throw new  MongoDBException('data is can only be a two-dimensional array');
        }
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->insertAll($namespace, $data);
        } catch (MongoDBException $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 数据插入数据库
     *
     * @param $namespace
     * @param array $data
     * @return bool|mixed
     * @throws MongoDBException
     */
    public function insert($namespace, array $data = [])
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->insert($namespace, $data);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 更新数据满足$filter的行的信息成$newObject
     *
     * @param $namespace
     * @param array $filter
     * @param array $newObj
     * @param bool $isIdAuto
     * @return bool
     * @throws MongoDBException
     */
    public function updateRow($namespace, array $filter = [], array $newObj = [],bool $isIdAuto = true): bool
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->updateRow($namespace, $filter, $newObj,$isIdAuto);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 只更新数据满足$filter的行的列信息中在$newObject中出现过的字段
     *
     * @param $namespace
     * @param array $filter
     * @param array $newObj
     * @param bool $isIdAuto
     * @return bool
     * @throws MongoDBException
     */
    public function updateColumn($namespace, array $filter = [], array $newObj = [],bool $isIdAuto = true): bool
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->updateColumn($namespace, $filter, $newObj,$isIdAuto);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 删除满足条件的数据，默认只删除匹配条件的全部行，若只删除第一行，limit设置为true
     * @param string $namespace
     * @param array $filter
     * @param bool $isIdAuto
     * @param bool $limit
     * @return bool
     * @throws MongoDBException
     */
    public function delete(string $namespace, array $filter = [],bool $isIdAuto = true, bool $limit = false): bool
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->delete($namespace, $filter,$isIdAuto, $limit);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 返回collection中满足条件的数量
     *
     * @param string $namespace
     * @param array $filter
     * @return bool
     * @throws MongoDBException
     */
    public function count(string $namespace, array $filter = [])
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->count($namespace, $filter);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }


    /**
     * @param string $namespace
     * @param array $filter
     * @return bool
     * @throws MongoDBException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function command(string $namespace, array $filter = [])
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->command($namespace, $filter);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    public function findandmodify(string $namespace,array $filters,array $update){
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->findandmodify($namespace, $filters,$update);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 聚合查询
     * @param string $namespace
     * @param array $filter
     * @return bool
     * @throws MongoDBException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function selectWithGroupBy(string $namespace, array $filter = [])
    {
        try {
            /**
             * @var $collection MongoDBConnection
             */
            $collection = $this->getConnection();
            return $collection->selectWithGroupBy($namespace, $filter,true);
        } catch (\Exception $e) {
            throw new MongoDBException($e->getFile() . $e->getLine() . $e->getMessage());
        }
    }

    private function getConnection()
    {
        $connection = null;
        $hasContextConnection = Context::has($this->getContextKey());
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }
        if (!$connection instanceof MongoDbConnection) {
            $pool = $this->factory->getPool($this->poolName);
            $connection = $pool->get()->getConnection();
        }
        return $connection;
    }

    /**
     * The key to identify the connection object in coroutine context.
     */
    private function getContextKey(): string
    {
        return sprintf('mongodb.connection.%s', $this->poolName);
    }

}