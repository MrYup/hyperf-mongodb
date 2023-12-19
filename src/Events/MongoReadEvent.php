<?php

namespace Mryup\HyperfMongodb\Events;

/**
 * mongoDB find事件
 */
class MongoReadEvent
{

    public $db;
    public $collection;
    public $filters;
    public $options;
    public $processTime;

    /**
     * @param $db
     * @param $collection
     * @param $filters
     * @param $options
     */
    public function __construct($db,$collection,$filters,$options,$processTime)
    {
        $this->db = $db;
        $this->collection = $collection;
        $this->filters = $filters;
        $this->options = $options;
        $this->processTime = $processTime;
    }
}