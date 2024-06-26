<?php

namespace Mryup\HyperfMongodb;

use Carbon\Carbon;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\Utils\Traits\ForwardsCalls;
use Mryup\HyperfMongodb\Exception\MongoInsertException;
use Mryup\HyperfMongodb\Exception\MongoUpdateException;
use Hyperf\Di\Annotation\Inject;

abstract class MongodbModel
{

    use IgnoreNotFoundProperty,ForwardsCalls;

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    //软删除
    protected $softDeleted = false;

    //软删除字段名
    protected $deletedAt = 'deleted_at';

    /**
     * 是否自动更新时间
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * 连接池id
     * @var string
     */
    protected $connection = 'default';

    /**
     * 自定义主键
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 自定义主键自增
     * @var bool
     */
    protected $increase = true;


    /**
     * 数据库主键
     * @var mixed
     */
    private $_id = null;

    /**
     * 数据库主键，是否是数据库自动生成
     * @var bool
     */
    protected static $isIdAuto = true;

    /**
     * @Inject
     * @var MongoDb
     */
    protected $mongo;

    /**
     * @var MongoBuilder
     */
    protected $builder;


    public function __construct()
    {
        $this->builder = new MongoBuilder();
    }


    abstract public function getCollection();

    /**
     * @return string
     */
    public function getConnection(){
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isTimestamp(){
        return $this->timestamps;
    }

    public function isSoftDeleted(){
        return $this->softDeleted;
    }

    public function deletedAt(){
        return $this->deletedAt;
    }

    /**
     * @return MongoDb
     */
    public function getMongo(){
        return $this->mongo;
    }


    /**
     * 创建并写入一个对象
     * @param $insert
     * @return static
     * @throws Exception\IDIncreaseException
     * @throws Exception\MongoDBException
     * @throws MongoInsertException
     */
    public static function create($insert){
        $instance = (new static());

        //自定义id，需要维护自增Id
        if ($instance->increase){
            $newId = (new AutoIdGenerator($instance->mongo,$instance->getCollection()))->getId();
            $insert[$instance->primaryKey] = $newId;
        }

        //时间字段
        if ($instance->timestamps){
            $insert[self::CREATED_AT] = Carbon::now()->toDateTimeString();
            $insert[self::UPDATED_AT] = null;
        }
        self::formatWrittenRow($insert);

        foreach ($insert as $field => $value){
            $instance->$field = $value;
        }

        //设置id，表示一个已经落地的对象
        $instance->_id =  $instance->mongo->insert($instance->getCollection(),$insert);
        if (!$instance->_id){
            throw new MongoInsertException("Insert data failed");
        }

        return $instance;

    }



    /**
     * 查找第一行，否则抛出异常
     * @param array $filters
     * @param array $select
     * @return static
     * @throws Exception\MongoDBException
     */
    public static function firstOrFail(array $filters = [],array $select = []){
        $instance = static::query()->filters($filters)->select($select)->first();
        if (!$instance){
            throw (new ModelNotFoundException())->setModel(static::class);
        }
        return $instance;
    }



    /**
     * @param array $filters
     * @param array $attributes
     * @return static
     * @throws Exception\MongoDBException
     */
    public static function updateOrCreate(array $filters,array $attributes = []){
        $instance = static::query()->filters($filters)->first();

        if (!$instance){
            return self::create(array_merge($filters,$attributes));
        }else{
            foreach ($attributes as $field => $value){
                $instance->$field = $value;
            }
            return $instance->save();
        }
    }

    /**
     * @param array $filters
     * @param array $attributes
     * @return static
     * @throws Exception\MongoDBException
     */
    public static function firstOrCreate(array $filters,array $attributes = []){
        $instance = static::query()->filters($filters)->first();

        if (!$instance){
            $instance = self::create(array_merge($filters,$attributes));
        }

        return $instance;
    }

    /**
     * 更新所有行
     * @param array $filters
     * @param array $attributes
     * @return bool
     * @throws Exception\MongoDBException
     */
    public static function updateAll(array $filters,array $attributes = []){
        $instance = new static();
        //更新时间
        if ($instance->timestamps){
            $attributes[self::CREATED_AT] = Carbon::now()->toDateTimeString();
        }
        self::formatWrittenRow($attributes);

        $instance->mongo->updateRow($instance->getCollection(),$filters,$attributes,static::$isIdAuto);
        return true;
    }


    /**
     * @param array $filters
     * @param bool $forceDeleted
     * @return bool
     * @throws Exception\MongoDBException
     */
    public static function deleteAll(array $filters,bool $forceDeleted = false){
        $instance = new static();

        if ($instance->softDeleted && !$forceDeleted){
            //软删除
            $attributes[$instance->deletedAt] = Carbon::now()->toDateTimeString();
            self::updateAll($filters,$attributes);
        }else{
            //强制删除
            $instance->mongo->delete($instance->getCollection(),$filters,static::$isIdAuto);
        }
        return true;
    }


    protected function writeByFilter(){
        if (!$this->_id){
            throw new MongoUpdateException("Instance missing writeId");
        }

        return ['_id' => $this->_id];
    }


    /**
     * 删除当前Eloquent
     * @return bool $forceDeleted
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function delete(bool $forceDeleted = false){
        if (!$this->_id){
            throw new MongoUpdateException("Instance missing _id");
        }
        if ($this->softDeleted && !$forceDeleted){
            //软删除
            $this->{$this->deletedAt} = Carbon::now()->toDateTimeString();
            $this->save();

            foreach ($this->toArray() as $field => $value){
                unset($this->$field);
            }
        }else{
            //强制删除
            $this->mongo->delete($this->getCollection(),$this->writeByFilter(),static::$isIdAuto,true);
        }
        return true;
    }


    /**
     * 只更新指定字段
     * @param array $attributes
     * @return $this
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function update(array $attributes = []){
        //更新时间
        if ($this->timestamps){
            $attributes[self::UPDATED_AT] = Carbon::now()->toDateTimeString();
        }
        self::formatWrittenRow($attributes);

        $this->mongo->updateColumn($this->getCollection(),$this->writeByFilter(),$attributes,static::$isIdAuto);

        foreach ($attributes as $field => $value){
            $this->$field = $value;
        }

        return $this;
    }



    /**
     * @return MongoEloquent
     */
    public static function query(){
        return make(MongoEloquent::class,['model'=>new static()]);
    }


    /**
     * 更新当前Eloquent(全部字段覆盖)
     * @return $this
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function save(){
        if ($this->timestamps){
            $ut = self::UPDATED_AT;
            $this->$ut = Carbon::now()->toDateTimeString();
        }

        $update = $this->toArray();
        self::formatWrittenRow($update);

        $this->mongo->updateColumn($this->getCollection(),$this->writeByFilter(),$update,static::$isIdAuto);

        return $this;
    }


    /**
     * 字段递增或递减，原子性
     * @param $column
     * @param int $step     -步差
     * @param bool $up      -true:递增，false:递减
     * @return $this
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function inc($column,int $step = 1,bool $up = true){
        $step = $up?$step:0-$step;
        $incrInfo =  $this->mongo->findandmodify($this->getCollection(),$this->writeByFilter(),['$inc'=>[$column=>$step]]);
        $this->{$column} = $incrInfo->value->{$column};
        return $this;
    }
    

    /**
     * @return bool
     */
    public function isAutoId(){
        return static::$isIdAuto;
    }




    /**
     * 入库数据格式化
     */
    public static function formatWrittenRow(&$rows){
        foreach ($rows as $field => &$value){
            if ($value instanceof Carbon){
                //日期类型字段
                $value = $value->toDateTimeString();
            }else{
                //对象转数组
                if (is_object($value)){
                    //对象转数组
                    if (method_exists($value,'toArray')){
                        $value = $value->toArray();
                    }else{
                        $value = json_decode(json_encode($value),true);
                    }
                }
            }


        }
    }


}