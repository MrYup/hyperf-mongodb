<?php

namespace Mryup\HyperfMongodb;

use Mryup\HyperfMongodb\Exception\IDIncreaseException;

class AutoIdGenerator
{
    /**
     * @var MongoDb
     */
    private $mongoDb;

    /**
     * 需要自增id的集合名
     * @var
     */
    private $collection;

    //保存系统全部表自增id的集合名称
    const SYSTEM_ID_COLLECTION = 'systemIds';

    public function __construct(MongoDb $mongoDb,$collection)
    {
        $this->mongoDb = $mongoDb;
        $this->collection = $collection;

    }


    /**
     * 利用findandmodify的原子性，每次+1最大id
     * @return mixed
     * @throws Exception\MongoDBException
     * @throws IDIncreaseException
     */
    public function getId(){
        $incrInfo =  $this->mongoDb->findandmodify(self::SYSTEM_ID_COLLECTION,['collection'=>$this->collection],['$inc'=>['maxId'=>1]]);
        if (!isset($incrInfo->value->maxId)){
            throw new IDIncreaseException("Fail to increase and get new id for collection {$this->collection} ");
        }
        return $incrInfo->value->maxId;
    }

}