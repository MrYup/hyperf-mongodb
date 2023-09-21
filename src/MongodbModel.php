<?php

namespace Mryup\HyperfMongodb;

use App\Model\BankCode;
use Carbon\Carbon;
use Hyperf\Contract\CastsAttributes;
use Hyperf\Database\Model\ModelNotFoundException;
use MongoDB\BSON\ObjectId;
use Mryup\HyperfMongodb\Exception\MongoInsertException;
use Mryup\HyperfMongodb\Exception\MongoUpdateException;

abstract class MongodbModel
{

    use IgnoreNotFoundProperty;

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    const DESC = -1;

    const ASC = 1;

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
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 主键自增
     * @var bool
     */
    protected $increase = true;

    /**
     * @var MongoDb
     */
    protected $mongo;

    private $_id = null;

    private function __construct()
    {
        $this->mongo = make(MongoDb::class)->setPool($this->connection);
    }


    abstract public function getCollection();


    /**
     * 查找第一行
     * @param array $filters
     * @param array $options
     * @return static|null
     * @throws Exception\MongoDBException
     */
    public static function first(array $filters = [],array $options = []){
        $instance = (new static());
        $options['sort'] = ['_id'=>self::ASC];
        $row = $instance->mongo->findOne($instance->getCollection(),$filters,$options);
        if (empty($row)){
            return null;
        }
        foreach ($row as $field => $value ){
            $instance->$field = $value;
        }

        return $instance;
    }


    /**
     * 查找第一行，否则抛出异常
     * @param array $filters
     * @param array $options
     * @return MongodbModel
     * @throws Exception\MongoDBException
     */
    public static function firstOrFail(array $filters = [],array $options = []){
        $instance = self::first($filters,$options);
        if (!$instance){
            throw (new ModelNotFoundException())->setModel(static::class);
        }
        return $instance;
    }


    /**
     * 查找最后一行
     * @param array $filters
     * @param array $options
     * @return static|null
     * @throws Exception\MongoDBException
     */
    public static function last(array $filters = [],array $options = []){
        $instance = (new static());
        $options['sort'] = ['_id'=>self::DESC];
        $row = $instance->mongo->findOne($instance->getCollection(),$filters,$options);
        if (empty($row)){
            return null;
        }
        foreach ($row as $field => $value ){
            $instance->$field = $value;
        }

        return $instance;
    }

    /**
     * 查询全部Eloquent
     * @param array $filters
     * @param array $options
     * @return static[]
     * @throws Exception\MongoDBException
     */
    public static function findAll(array $filters = [],array $options = []){
        $instance = (new static());
        $ret = [];
        $rows = $instance->mongo->fetchAll($instance->getCollection(),$filters,$options);

        foreach ($rows as $row){
            $ret[] = self::makeByArray($row);
        }
        return $ret;
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
     * 更新第一行
     * @param array $filters
     * @param array $attributes
     * @return bool
     * @throws Exception\MongoDBException
     */
    public static function update(array $filters,array $attributes = []){
        $instance = new static();
        //更新时间
        if ($instance->timestamps){
            $attributes[self::CREATED_AT] = Carbon::now()->toDateTimeString();
        }
        self::formatWrittenRow($attributes);

        $instance->mongo->updateColumn($instance->getCollection(),$filters,$attributes);
        return true;
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

        $instance->mongo->updateRow($instance->getCollection(),$filters,$attributes);
        return true;
    }


    public static function deleteAll(array $filters){
        $instance = new static();
        $instance->mongo->delete($instance->getCollection(),$filters);
        return true;
    }


    /**
     * @param array $filters
     * @param array $attributes
     * @return static
     * @throws Exception\MongoDBException
     */
    public static function updateOrCreate(array $filters,array $attributes = []){
        $instance = self::first($filters);

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
     * 删除当前Eloquent
     * @return bool
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function delete(){
        if (!$this->_id){
            throw new MongoUpdateException("Instance missing _id");
        }
        $this->mongo->delete($this->getCollection(),['_id'=>$this->_id],true);
        return true;
    }


    /**
     * 更新当前Eloquent(全部字段覆盖)
     * @return $this
     * @throws Exception\MongoDBException
     * @throws MongoUpdateException
     */
    public function save(){
        if (!$this->_id){
            throw new MongoUpdateException("Instance missing _id");
        }
        if ($this->timestamps){
            $ut = self::UPDATED_AT;
            $this->$ut = Carbon::now()->toDateTimeString();
        }

        $update = $this->toArray();
        self::formatWrittenRow($update);

        $this->mongo->updateColumn($this->getCollection(),['_id'=>new ObjectId($this->_id)],$update);

        return $this;
    }




    /**
     * 入库数据格式化
     */
    protected static function formatWrittenRow(&$rows){
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


    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name??null;
    }
}