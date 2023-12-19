<?php

namespace Mryup\HyperfMongodb\Events;

/**
 * mongoDB 写入事件
 */
class MongoWriteEvent
{

    public $commandTy;
    public $db;
    public $collection;
    public $filters = [];
    public $options = [];
    public $newData = [];
    public $processTime;
    public function __construct($db,$collection,$commandTy,array $filters = [],array $options = [],array $newData = [],$processTime = null)
    {
        $this->db = $db;
        $this->collection = $collection;
        $this->commandTy = $commandTy;
        $this->filters = $filters;
        $this->options = $options;
        $this->newData = $newData;
        $this->processTime = $processTime;

    }
}