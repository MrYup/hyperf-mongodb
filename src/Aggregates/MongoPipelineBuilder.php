<?php

namespace Mryup\HyperfMongodb\Aggregates;

use Mryup\HyperfMongodb\MongoBuilder;

class MongoPipelineBuilder extends MongoBuilder
{
    /**
     * pipeline $lookup，可设置多个，表示关联多个collection
     * @var Lookup[]
     */
    protected $lookup = [];

    /**
     * 查询字段，与find()的project语法一致
     * @var array
     */
    protected $projections = [];

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * offset
     * @var int|null
     */
    protected $skip;

    /**
     * 模仿mysql的having，与find()的filters语法一致
     * @var array
     */
    protected $having = [];

    public function first(){

    }

    public function last(){

    }

    public function all(){

    }

    public function delete(){

    }

    public function update(){

    }

}