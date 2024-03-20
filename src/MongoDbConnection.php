<?php

namespace Mryup\HyperfMongodb;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Event\EventDispatcher;
use Mryup\HyperfMongodb\Events\MongoReadEvent;
use Mryup\HyperfMongodb\Events\MongoWriteEvent;
use Mryup\HyperfMongodb\Exception\MongoDBException;
use Hyperf\Pool\Connection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\AuthenticationException;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;
use Psr\Container\ContainerInterface;

class MongoDbConnection extends Connection implements ConnectionInterface
{
    /**
     * @var Manager
     */
    protected $connection;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $config;

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);

        $this->eventDispatcher = $container->get(EventDispatcher::class);
        $this->config = $config;
        $this->reconnect();
    }

    public function getActiveConnection()
    {
        // TODO: Implement getActiveConnection() method.
        if ($this->check()) {
            return $this;
        }
        if (!$this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }
        return $this;
    }

    /**
     * Reconnect the connection.
     */
    public function reconnect(): bool
    {
        // TODO: Implement reconnect() method.
        try {
            /**
             * http://php.net/manual/zh/mongodb-driver-manager.construct.php
             */

            //优先使用自定义的url
            $url = $this->config['url']??'';
            $urlOptions = [];

            if (!$url){
                $username = $this->config['username'];
                $password = $this->config['password'];
                if (!empty($username) && !empty($password)) {
                    $url = sprintf(
                        'mongodb://%s:%s@%s:%d/%s',
                        $username,
                        $password,
                        $this->config['host'],
                        $this->config['port'],
                        $this->config['db']
                    );
                } else {
                    $url = sprintf(
                        'mongodb://%s:%d/%s',
                        $this->config['host'],
                        $this->config['port'],
                        $this->config['db']
                    );
                }
                //数据集
                $replica = $this->config['replica']?? null;
                if ($replica) {
                    $urlOptions['replicaSet'] = $replica;
                }
            }

            $this->connection = new Manager($url, $urlOptions);
        } catch (InvalidArgumentException $e) {
            throw MongoDBException::managerError('mongodb 连接参数错误:' . $e->getMessage());
        } catch (RuntimeException $e) {
            throw MongoDBException::managerError('mongodb uri格式错误:' . $e->getMessage());
        }
        $this->lastUseTime = microtime(true);
        return true;
    }

    /**
     * Close the connection.
     */
    public function close(): bool
    {
        unset($this->connection);
        return true;
    }


    /**
     * 查询返回结果的全部数据
     *
     * @param string $namespace
     * @param array $filter
     * @param array $options
     * @param bool $isIdAuto
     * @return array
     * @throws MongoDBException
     */
    public function executeQueryAll(string $namespace, array $filter = [], array $options = [],bool $isIdAuto = true)
    {
        if (isset($filter['_id'])){
            $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
        }
        // 查询数据
        $result = [];
        try {
            $query = new Query($filter, $options);
            $startTime = microtime(true);
            $cursor = $this->connection->executeQuery($this->config['db'] . '.' . $namespace, $query);
            $endTime = microtime(true);

            //触发查询事件
            $this->eventDispatcher->dispatch(new MongoReadEvent($this->config['db'],$namespace,$filter,$options,round($endTime - $startTime,4)));
            foreach ($cursor as $document) {
                $document = (array)$document;
                $document['_id'] = (string)$document['_id'];
                $result[] = $document;
            }
            $this->pool->release($this);
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
        return $result;
    }

    /**
     * 返回分页数据，默认每页10条
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
    public function execQueryPagination(string $namespace, int $limit = 10, int $currentPage = 0, array $filter = [], array $options = [],bool $isIdAuto = true)
    {
        if (isset($filter['_id'])){
            $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
        }
        // 查询数据
        $data = [];
        $result = [];

        //每次最多返回10条记录
        if (!isset($options['limit']) || (int)$options['limit'] <= 0) {
            $options['limit'] = $limit;
        }

        if (!isset($options['skip']) || (int)$options['skip'] <= 0) {
            $options['skip'] = $currentPage * $limit;
        }

        try {
            $query = new Query($filter, $options);
            $startTime = microtime(true);
            $cursor = $this->connection->executeQuery($this->config['db'] . '.' . $namespace, $query);
            $endTime = microtime(true);

            //触发查询事件
            $this->eventDispatcher->dispatch(new MongoReadEvent($this->config['db'],$namespace,$filter,$options,round($endTime - $startTime,4)));
            foreach ($cursor as $document) {
                $document = (array)$document;
                $document['_id'] = (string)$document['_id'];
                $data[] = $document;
            }

            $result['totalCount'] = $this->count($namespace, $filter,$isIdAuto);
            $result['currentPage'] = $currentPage;
            $result['perPage'] = $limit;
            $result['list'] = $data;
            $this->pool->release($this);
            return $result;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 数据插入
     * http://php.net/manual/zh/mongodb-driver-bulkwrite.insert.php
     * $data1 = ['title' => 'one'];
     * $data2 = ['_id' => 'custom ID', 'title' => 'two'];
     * $data3 = ['_id' => new MongoDB\BSON\ObjectId, 'title' => 'three'];
     *
     * @param string $namespace
     * @param array $data
     * @return bool|string
     * @throws MongoDBException
     */
    public function insert(string $namespace, array $data = [])
    {
        try {
            $startTime = microtime(true);
            $bulk = new BulkWrite();
            $insertId = (string)$bulk->insert($data);
            $written = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $this->connection->executeBulkWrite($this->config['db'] . '.' . $namespace, $bulk, $written);
            $endTime = microtime(true);

            //触发写入事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'INSERT',[],[],$data,round($endTime - $startTime,4)));
            $this->pool->release($this);
            return $insertId;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 批量数据插入
     * http://php.net/manual/zh/mongodb-driver-bulkwrite.insert.php
     * $data = [
     * ['title' => 'one'],
     * ['_id' => 'custom ID', 'title' => 'two'],
     * ['_id' => new MongoDB\BSON\ObjectId, 'title' => 'three']
     * ];
     * @param string $namespace
     * @param array $data
     * @return bool|string[]
     * @throws MongoDBException
     */
    public function insertAll(string $namespace, array $data = [])
    {
        try {
            $startTime = microtime(true);
            $insertId = [];
            $bulk = new BulkWrite();
            foreach ($data as $items) {
                $insertId[] = (string)$bulk->insert($items);
            }
            $written = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $this->connection->executeBulkWrite($this->config['db'] . '.' . $namespace, $bulk, $written);

            $endTime = microtime(true);
            //触发写入事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'INSERT',[],[],$data,round($endTime - $startTime,4)));
            $this->pool->release($this);
            return $insertId;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 数据更新,效果是满足filter的行,只更新$newObj中的$set出现的字段
     * http://php.net/manual/zh/mongodb-driver-bulkwrite.update.php
     * $bulk->update(
     *   ['x' => 2],
     *   ['$set' => ['y' => 3]],
     *   ['multi' => false, 'upsert' => false]
     * );
     * @param string $namespace
     * @param array $filter
     * @param array $newObj
     * @param bool $isIdAuto
     * @return bool
     * @throws \Throwable
     */
    public function updateRow(string $namespace, array $filter = [], array $newObj = [],bool $isIdAuto = true): bool
    {
        try {
            $startTime = microtime(true);
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }
            $option = ['multi' => true, 'upsert' => false];
            $bulk = new BulkWrite;
            $bulk->update(
                $filter,
                ['$set' => $newObj],
                $option
            );
            $written = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result = $this->connection->executeBulkWrite($this->config['db'] . '.' . $namespace, $bulk, $written);
            $modifiedCount = $result->getModifiedCount();
            $endTime = microtime(true);

            //触发写入事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'UPDATE',$filter,$option,$newObj,round($endTime - $startTime,4)));
            $this->pool->release($this);
            return $modifiedCount == 0 ? false : true;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 数据更新, 效果是满足filter的行数据更新成$newObj
     * http://php.net/manual/zh/mongodb-driver-bulkwrite.update.php
     * $bulk->update(
     *   ['x' => 2],
     *   [['y' => 3]],
     *   ['multi' => false, 'upsert' => false]
     * );
     *
     * @param string $namespace
     * @param array $filter
     * @param array $newObj
     * @param bool $isIdAuto
     * @return bool
     * @throws MongoDBException
     */
    public function updateColumn(string $namespace, array $filter = [], array $newObj = [],bool $isIdAuto = true): bool
    {
        try {
            $startTime = microtime(true);
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }

            $option = ['multi' => false, 'upsert' => false];
            $bulk = new BulkWrite;
            $bulk->update(
                $filter,
                ['$set' => $newObj],
                $option,
            );
            $written = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result = $this->connection->executeBulkWrite($this->config['db'] . '.' . $namespace, $bulk, $written);
            $modifiedCount = $result->getModifiedCount();
            $endTime = microtime(true);
            //触发写入事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'UPDATE',$filter,$option,$newObj,round($endTime - $startTime,4)));
            $this->pool->release($this);
            return $modifiedCount == 1 ? true : false;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 删除数据
     * @param string $namespace
     * @param array $filter
     * @param bool $isIdAuto
     * @param bool $limit
     * @return bool
     * @throws \Throwable
     */
    public function delete(string $namespace, array $filter = [],bool $isIdAuto = true, bool $limit = false): bool
    {
        try {
            $startTime = microtime(true);
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }
            $option = ['limit' => $limit];
            $bulk = new BulkWrite;
            $bulk->delete($filter, $option);
            $written = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $this->connection->executeBulkWrite($this->config['db'] . '.' . $namespace, $bulk, $written);
            $endTime = microtime(true);
            //触发写入事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'DELETE',$filter,$option,[],round($endTime - $startTime,4)));
            $this->pool->release($this);
            return true;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    /**
     * 格式化_id
     * @param mixed $_id
     * @param bool $isIdAuto
     * @return array
     */
    protected function formatFilters($_id ,bool $isIdAuto = true){
        if ($_id && !($_id instanceof ObjectId) && $isIdAuto) {
            if (is_array($_id)){
                foreach ($_id as $op => $idFilterValue){
                    if (is_array($idFilterValue)){
                        $_id[$op] = $this->formatFilters($idFilterValue,$isIdAuto);
                    }else{
                        $_id[$op] = new ObjectId($idFilterValue);
                    }
                }
            }else{
                $_id = new ObjectId($_id);
            }
        }

        return $_id;
    }

    /**
     * 获取collection 中满足条件的条数
     *
     * @param string $namespace
     * @param array $filter
     * @return bool
     * @throws MongoDBException
     */
    public function count(string $namespace, array $filter = [],bool $isIdAuto = true)
    {
        try {
            $startTime = microtime(true);
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }
            $commandParam = [
                'count' => $namespace
            ];
            if (!empty($filter)) {
                $commandParam['query'] = $filter;
            }
            $command = new Command($commandParam);
            $cursor = $this->connection->executeCommand($this->config['db'], $command);
            $endTime = microtime(true);
            //触发查询事件
            $this->eventDispatcher->dispatch(new MongoReadEvent($this->config['db'],$namespace,$filter,[],round($endTime - $startTime,4)));
            $count = $cursor->toArray()[0]->n;
            $this->pool->release($this);
            return $count;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }


    /**
     * @param string $namespace
     * @param array $filter
     * @param bool $fetchAll
     * @return array|mixed
     * @throws Exception
     * @throws \Throwable
     */
    public function selectWithGroupBy(string $namespace, array $filter = [], bool $fetchAll = false,bool $isIdAuto = true)
    {
        try {
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }
            $startTime = microtime(true);
            $command = new Command([
                'aggregate' => $namespace,
                'pipeline' => $filter,
                'cursor' => new \stdClass()
            ]);
            $cursor = $this->connection->executeCommand($this->config['db'], $command);
            $endTime = microtime(true);
            //触发查询事件
            $this->eventDispatcher->dispatch(new MongoReadEvent($this->config['db'],$namespace,$filter,[],round($endTime - $startTime,4)));
            $asArr = $cursor->toArray();
            $this->pool->release($this);
            return  $fetchAll?$asArr:($asArr[0]??[]);
        } catch (\Throwable $e) {
            $this->pool->release($this);
            throw $e;
        }
    }

    public function findandmodify(string $namespace,array $filter,array $update,bool $isIdAuto = true){
        try {
            if (isset($filter['_id'])){
                $filter['_id'] = $this->formatFilters($filter['_id'],$isIdAuto);
            }
            $command = new Command([
                'findandmodify' => $namespace,
                'update' => $update,
                'query' => $filter,
                'new' => true,
                'upsert' => true
            ]);
            $startTime = microtime(true);
            $result =  $this->connection->executeCommand($this->config['db'], $command)->toArray();
            $endTime = microtime(true);
            //触发查询事件
            $this->eventDispatcher->dispatch(new MongoWriteEvent($this->config['db'],$namespace,'UPDATE',$filter,[],$update,round($endTime - $startTime,4)));
            $this->pool->release($this);
            return $result[0]??null;
        } catch (\Throwable $e) {
            $this->pool->release($this);
            return $this->catchMongoException($e);
        }
    }

    /**
     * 判断当前的数据库连接是否已经超时
     *
     * @return bool
     * @throws \MongoDB\Driver\Exception\Exception
     * @throws MongoDBException
     */
    public function check(): bool
    {
        try {
            $command = new Command(['ping' => 1]);
            $this->connection->executeCommand($this->config['db'], $command);
            return true;
        } catch (\Throwable $e) {
            return $this->catchMongoException($e);
        }
    }

    /**
     * @param \Throwable $e
     * @return bool
     * @throws MongoDBException
     */
    private function catchMongoException(\Throwable $e)
    {
        switch ($e) {
            case ($e instanceof InvalidArgumentException):
                {
                    throw MongoDBException::managerError('mongo argument exception: ' . $e->getMessage());
                }
            case ($e instanceof AuthenticationException):
                {
                    throw MongoDBException::managerError('mongo数据库连接授权失败:' . $e->getMessage());
                }
            case ($e instanceof ConnectionException):
                {
                    /**
                     * https://cloud.tencent.com/document/product/240/4980
                     * 存在连接失败的，那么进行重连
                     */
                    for ($counts = 1; $counts <= 5; $counts++) {
                        try {
                            $this->reconnect();
                        } catch (\Exception $e) {
                            continue;
                        }
                        break;
                    }
                    return true;
                }
            case ($e instanceof RuntimeException):
                {
                    throw MongoDBException::managerError('mongo runtime exception: ' . $e->getMessage());
                }
            default:
                {
                    throw MongoDBException::managerError('mongo unexpected exception: ' . $e->getMessage());
                }
        }
    }
}
