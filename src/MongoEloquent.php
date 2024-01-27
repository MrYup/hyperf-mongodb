<?php

namespace Mryup\HyperfMongodb;

use Hyperf\Utils\Traits\ForwardsCalls;

/**
 * @mixin MongoBuilder
 */
class MongoEloquent
{
    use ForwardsCalls;
    /**
     * @var MongodbModel
     */
    private $model;

    /**
     * @var MongoBuilder
     */
    private $builder;

    public function __construct(MongodbModel $model)
    {
        $this->model = $model;
        $this->builder = new MongoBuilder();
    }

    public function __call($name, $arguments)
    {
         $this->forwardCallTo($this->builder,$name,$arguments);
         return $this;
    }

    /**
     * 查询最第一个Eloquent
     * @param string $sortBy
     * @param bool $first
     * @return MongodbModel|null
     * @throws Exception\MongoDBException
     */
    public  function first(string $sortBy = '_id',bool $first = true){

        //排序
        if ($first){
            $this->orderByASC($sortBy);
        }else{
            $this->orderByDesc($sortBy);
        }

        //过滤软删除
        if ($this->model->isSoftDeleted()){
            $this->builder->whereNull($this->model->deletedAt());
        }

        //mongo find() filters
        $filters = $this->builder->getFilters();


        //mongo find() option
        $options = $this->builder->getOptions();


        $row = $this->model->getMongo()->findOne($this->model->getCollection(),$filters,$options,$this->model->isAutoId());
        if (empty($row)){
            return null;
        }
        foreach ($row as $field => $value ){
            $this->model->$field = $value;
        }

        return $this->model;
    }


    /**
     * 查询最后一个Eloquent
     * @param string $sortBy
     * @return MongodbModel|null
     * @throws Exception\MongoDBException
     */
    public  function last(string $sortBy = '_id'){
        return $this->first($sortBy,false);
    }


    /**
     * 查询全部Eloquent
     * @return MongodbModel[]
     * @throws Exception\MongoDBException
     */
    public  function all(){

        //过滤软删除
        if ($this->model->isSoftDeleted()){
            $this->builder->whereNull($this->model->deletedAt());
        }

        //mongo find() filters
        $filters = $this->builder->getFilters();


        //mongo find() option
        $options = $this->builder->getOptions();

        $rows = $this->model->getMongo()->fetchAll($this->model->getCollection(),$filters,$options,$this->model->isAutoId());
        if (empty($rows)){
            return [];
        }

        foreach ($rows as $index =>  $row){
            $model = clone $this->model;
            foreach ($row as $field => $value ){
                $model->$field = $value;
            }
            $rows[$index] = $model;
        }

        return $rows;
    }

    /**
     * 查询并返回字段字段，一维数组
     * @param $column
     * @return array
     * @throws Exception\MongoDBException
     */
    public function value($column){
        $this->select([$column]);

        //过滤软删除
        if ($this->model->isSoftDeleted()){
            $this->builder->whereNull($this->model->deletedAt());
        }

        //mongo find() filters
        $filters = $this->builder->getFilters();


        //mongo find() option
        $options = $this->builder->getOptions();

        $rows = $this->model->getMongo()->fetchAll($this->model->getCollection(),$filters,$options,$this->model->isAutoId());
        if (empty($rows)){
            return [];
        }

        return array_column($rows,$column);
    }

}