# hyperf mongodb pool

```
composer require mryup/hyperf-mongodb
```

## 前言

Fork 与 ```yumufeng/hyperf-mongodb```包，做了一些个性化修改
感谢大佬的开源奉献，如若侵权，烦请告知下架

## config 
在/config/autoload目录里面创建文件 mongodb.php
添加以下内容
```php
return [
    'default' => [
        'username' => env('MONGODB_USERNAME', ''),
        'password' => env('MONGODB_PASSWORD', ''),
        'host' => env('MONGODB_HOST', '127.0.0.1'),
        'port' => env('MONGODB_PORT', 27017),
        'db' => env('MONGODB_DB', 'test'),
        'authMechanism' => 'SCRAM-SHA-256',
        //设置复制集,没有不设置
        'replica' => 'rs0',
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 100,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float)env('MONGODB_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            //维护数据库每张表的自增id集合名称
            'id_collector' => env('MONGODB_ID_COLLECTOR'),
        ],
    ],
];
```


# 使用案例

使用注解，自动加载 
**\Hyperf\Mongodb\MongoDb** 
```php
/**
 * @Inject()
 * @var MongoDb
*/
 protected $mongoDbClient;
```

#### **tips:** 
查询的值，是严格区分类型，string、int类型的哦

### 新增

单个添加
```php
$insert = [
            'account' => '',
            'password' => ''
];
$this->$mongoDbClient->insert('fans',$insert);
```

批量添加
```php
$insert = [
            [
                'account' => '',
                'password' => ''
            ],
            [
                'account' => '',
                'password' => ''
            ]
];
$this->$mongoDbClient->insertAll('fans',$insert);
```

### 查询

```php
$where = ['account'=>'1112313423'];
$result = $this->$mongoDbClient->fetchAll('fans', $where);
```

### 分页查询
```php
$list = $this->$mongoDbClient->fetchPagination('article', 10, 0, ['author' => $author]);
```

### 更新
```php
$where = ['account'=>'1112313423'];
$updateData = [];

$this->$mongoDbClient->updateColumn('fans', $where,$updateData); // 只更新数据满足$where的行的列信息中在$newObject中出现过的字段
$this->$mongoDbClient->updateRow('fans',$where,$updateData);// 更新数据满足$where的行的信息成$newObject
```
### 删除

```php
$where = ['account'=>'1112313423'];
$all = true; // 为true只删除匹配的一条，true删除全部
$this->$mongoDbClient->delete('fans',$where,$all);
```

### count统计

```php
$filter = ['isGroup' => "0", 'wechat' => '15584044700'];
$count = $this->$mongoDbClient->count('fans', $filter);
```

### Eloquent

- 手动创建一个model，继承 `Mryup\HyperfMongodb\MongodbModel`，(这里做了JWT鉴权，可忽略)

```php

<?php

namespace App\Model;

use MongoDB\BSON\ObjectId;
use Mryup\HyperfMongodb\MongodbModel;
use Qbhy\HyperfAuth\Authenticatable;


/**
 * @property $_id
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $email
 */
class DashboardUser extends MongodbModel implements Authenticatable
{
    protected $softDeleted = true;

    public function getCollection()
    {
        return 'test';
    }

    public static function retrieveById($key): ?Authenticatable
    {
        return self::firstOrFail(['_id'=>new ObjectId($key)]);
    }

    public function getId()
    {
        return $this->_id;
    }

}

```

### Eloquent Usage

```php
<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Cor\CoroutinePropertyTrait;
use App\Cor\MchPasswordAes;
use App\Logic\User\IUser;
use App\Model\DashboardUser;
use App\Model\User;
use App\Olsq;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Mryup\HyperfMongodb\MongoDb;

class MongoTestController extends AbstractController
{
    use CoroutinePropertyTrait;

    use MchPasswordAes;

    /**
     * @Inject
     * @var MongoDb
     */
    protected $mongoDbClient;

    //insert
    public function insert(){
        $ids = [
            10001,10002,10003
        ];
        $bankCodes = [
            'BCA','BNI','PERMATA','BNC','MANDIRI','BRI'
        ];
        $channelIds = [
            1,31,65,13,53,431,443
        ];
        $insert = [];
        for ($i=1;$i<=50;$i++){
            $insert[] = [
                'c1' => '',
                'c2' => null,
                'c3' => false,
                'c4' => new Olsq(),
                'c5' => Carbon::now()->toDateTimeString(),
                'c6' => [
                    'aswqq' => 556646,
                    'dqwqwww' => new Olsq(),
                    'wdqewqwe'=> '879797979',
                    'sqwqweqw' => false,
                    'gieee' => null,
                    'wqeqweqwr' => Carbon::now(),
                    'fwwerwr' => '54876999',
                    'twewrt' => 'nnnnnnnnnnnnnn1',
                    'hrw' => randomStr(500),
                ],
                'c7' => randomStr(30),
                'c8' => 999995449874984646464645464646,
                'c9' => [
                    [
                        'eqwrrq' => 'saqwee',
                        'geewe' => '7844466',
                    ] ,
                    [
                        'eqwrrq' => 'saqwee',
                        'geewe' => '7844466',
                    ] ,
                    [
                        'eqwrrq' => 'saqwee',
                        'geewe' => '7844466',
                    ] ,
                ],
                'dat' => Carbon::now()->subSeconds(rand(1,9999999))->toDateTimeString(),
                'mchId' => $ids[rand(0,count($ids)-1)],
                'bankCode' => $bankCodes[rand(0,count($bankCodes)-1)],
                'channelId' => $channelIds[rand(0,count($channelIds)-1)],
                'amount' => 10,
                'fee' => 5
            ];
        }

        //单行 insert
        $this->mongoDbClient->insert('test',$insert[0]);
        //批量insert
        $r = $this->mongoDbClient->insertAll('test',$insert);

        return [
            'rst' => $r,
        ];
    }


    //条件查询1
    public function select(){
        $filters = [
            //多级字段等值查询
//            'c6' => ['twewrt'=>'111111111111sss']，
//            'c6.twewrt' => '111111111111sss',

            //多级字段in查询
//            'c6.dqwqwww.nwq' => ['$in'=>['111222222222bb']],

             //一级字段等值查询
//            'dat' => ['$eq'=>'2023-09-17 04:28:22'],

              //大于等于，小于等于查询
//            'c6.wdqewqwe' => ['$gt'=>'879797979','$lte'=>'879797981'],

            //或查询
//            '$or' => [
//                ['c7' => ['$eq'=>'kdWRuZHqdwRPUuse8HfCttdBHoWJKb'],'c6.wdqewqwe'=>['$gt'=>'879797980']],
//                ['c8' => 66123],
//            ],

            //模糊查询
//            'c6.fwwerwr' => ['$regex'=>'看i看'],
//            'c6.fwwerwr' => ['$regex'=>'^fe464'],

            //区间查询
            'dat' => ['$gte'=>'2023-06-21 21:06:52','$lte'=>'2023-09-15 18:10:41'],

        ];

        $r = $this->mongoDbClient->fetchAll('test',$filters,[]);

        return [
            'result' => $r,
        ];
    }


    //分页查询
    public function selectWithPaginated(){
        $options = [
            'sort' => [
//                'dat' => 1,//升序
                'dat' => -1,//降序
                'c6.aswqq' => 1,
            ],
        ];
        $filters = [];
        $pageSize =  (int)$this->request->input('page_size',10);
        $page = ((int)$this->request->input('page',1))-1;
        $c = $this->mongoDbClient->count('test',$filters);
        $totalPage = ceil($c/$pageSize);
        $r = $this->mongoDbClient->fetchPagination('test',$pageSize,$page,$filters,$options);

        return [
            'count' => $c,
            'pageSize' => $pageSize,
            'page' => $page,
            'totalPages'  => $totalPage,
            'listVarType' => gettype($r),
            'list' => $r,
        ];
    }

    //聚合查询
    public function selectsBySummary(){
        $filters = [
            'dat' => ['$gte'=>'2023-06-21 21:06:52','$lte'=>'2023-09-15 18:10:41'],
        ];

        $pipeline = [
            [
                //match 放在group前，与mysql的where用法一致
                '$match' => $filters,
            ],
            [
                '$group' => [

                    //mysql group by
                    '_id' => [
                        'bankCode' => '$bankCode',
                        'mchId' => '$mchId',
                        'channelId' => '$channelId',
                    ],

                    //mysql 聚合字段 count(*) as totalOrders
                    'totalOrders'=>[
                        '$sum' => 1,
                    ],
                    //mysql 聚合字段sum(amount) as totalAmount
                    'totalAmount' => [
                        '$sum' => '$amount',
                    ],
                    //mysql 聚合字段avg avg(amount) as totalAmount
                    'avgAmount' => [
                        '$avg' => '$amount'
                    ],
                    'maxDat' => [
                        '$max' => '$dat',
                    ],
                    'minDat' => [
                        '$min' => '$dat'
                    ],
                ],
            ],
            [
                '$sort' => [
                    'totalOrders' => -1,
                ],
            ],
            [
                '$limit' => 1000,
            ],
            [
                //match 放在group后，与mysql的having用法一致
                '$match' => [
                    'totalOrders' => [
                        '$gte' => 2,
                    ],
                ],
            ],

        ];

        $r = $this->mongoDbClient->selectWithGroupBy('test',$pipeline);

        $toMysql = "SELECT bankCode,
                        mchId,
                        channelId,
                        count(*) as totalOrders,
                        sum(amount) as totalAmount,
                        avg(avgAmount) as avgAmount,
                        max(dat) as maxDat,
                        min(dat) as minDat
                    FROM XXX
                    WHERE `bat` between '2023-06-21 21:06:52' and '2023-09-15 18:10:41'
                    GROUP BY bankCode,mchId,channelId 
                    having totalOrders>=2
                    order by totalOrders desc 
                    LIMIT 1000
        ";

        return [
            'result' => $r,
        ];
    }

    public function selectWithGroupConcat(){
        $filters = [
            'dat' => ['$gte'=>'2023-06-21 21:06:52','$lte'=>'2023-09-15 18:10:41'],
        ];

        $pipelines  = [
            [
                '$match' => $filters,
            ],
            [
                //mysql select group_contact(bankCode) as bankCodes group by mchId
                '$group' =>[
                    '_id' => '$mchId',
                    'bankCodes' => [
                        '$push' => '$bankCode'
                    ],
                ],
            ],
        ];

        $r = $this->mongoDbClient->selectWithGroupBy('test',$pipelines);


        $toSql = "SELECT mchId group_concat(bankCode) as bankCodes 
                    FROM XXX 
                    WHERE `bat` between '2023-06-21 21:06:52' and '2023-09-15 18:10:41'
                    GROUP BY mchId 
                    ";
        return [
            'result' => $r,
        ];
    }


    //创建并落地一个Eloquent
    public function modelCreate(){
        $user = DashboardUser::create([
            'username'=>'Bob',
            'fewqw' => 4588455,
            'channel_id' => rand(1,5),
            'title' => randomStr(10),
            'position' => randomStr(10),
            'addr' => randomStr(5),
            'age_real' => rand(20,30)
        ]);
        return [
            'r' => $user,
        ];
    }

    //查找第一个Eloquent
    public function modelFindOne(){
        $user = DashboardUser::query()
            ->where('username','Bob')
            ->whereGt('create_time','2023-10-13 00:29:20',true)
            ->first();
        return [
            'r' => $user,
        ];
    }


    /**
     * 查找全部Eloquent，注意的是mongodb的查询字段条件，是全等查询，即除了值数据类型也必须满足
     * @return array
     * @throws \Mryup\HyperfMongodb\Exception\MongoBuilderException
     * @throws \Mryup\HyperfMongodb\Exception\MongoDBException
     * @throws \Mryup\HyperfMongodb\Exception\MongoUpdateException
     */
    public function modelAll(){
        $query = DashboardUser::query()
            ->where('username','Bob')
        ;

        $pageSize =  (int)$this->request->input('page_size',10);
        $page = ((int)$this->request->input('page',1));
        $offset = ($page - 1) * $pageSize;

        $inChannelId = $this->request->input('inChannelId',[]);
        $notAddr = $this->request->input('notAddr',[]);

        $between = $this->request->input('between');
        $notBetween = $this->request->input('notBetween');

        $nullColumn = $this->request->input('nullColumn');
        $notNullColumn = $this->request->input('notNullColumn');

        $like = $this->request->input('like');
        $notLike = $this->request->input('notLike');

        if (!empty($inChannelId)){
            foreach ($inChannelId as $k => $v){
                $inChannelId[$k] = (int)$v;
            }
            $query->whereIn('channel_id',$inChannelId);
        }

        if (!empty($notAddr)){
            $query->whereNotIn('addr',$notAddr);
        }

        if (!empty($between[0]) && !empty($between[1])){
            $query->whereBetween('age_real',[(int)$between[0],(int)$between[1]]);
        }

        if (!empty($notBetween[0]) && !empty($notBetween[1])){
            $query->whereNotBetween('create_time',[$notBetween[0],$notBetween[1]]);
        }

        if ($like){
            $query->whereLike('title',$like);
        }
        if ($notLike){
            $query->whereNotLike('position',$notLike);
        }

        if ($nullColumn){
            $query->whereNull($nullColumn);
        }
        if ($notNullColumn){
            $query->whereNotNull($notNullColumn);
        }

        $query->limit($pageSize)
            ->offset($offset)
            ->select(['id','name'])
        ;
        $users = $query->all();

        if (isset($users[0])){
            $beforeSaved = clone $users[0];
            $users[0]->age = 18;
            $users[0]->save();
        }else{
            $beforeSaved = null;
        }


        return [
            'before' => $beforeSaved,
            'all' => $users,
        ];
    }


    //查找最后一个Eloquent
    public function last(){
        $user = DashboardUser::query()
            ->where('username','Bob')
            ->whereGt('create_time','2023-10-13 00:29:20',true)
            ->last();
        return [
            'r' => $user,
        ];
    }
    
    //更新一个Eloquent
    public function save(){
        $user = User::query()
            ->where('username','admin')
            ->first();
//        make(IUser::class)->changePassword($user,'Kj1u5wf#1dib#&fd');
        make(IUser::class)->changePassword($user,'JHDI3FK5!DFDF7DFf');

        return [
            'r' => $user,
        ];
    }


    //删除Eloquent
    public function modelDelete(){
        $user = DashboardUser::query()
            ->where('username','Bob')
            ->whereGt('create_time','2023-10-13 00:29:20')
            ->first();
        $asArr = clone $user;
        $isDeleted = $user->delete();
        return [
            'r' => $isDeleted,
            'user' => $asArr,
        ];
    }



    //更新或写入一个Eloquent
    public function modelupdateOrCreate(){
        $user = DashboardUser::updateOrCreate([
            'username'=>'Bob2',
        ],[
            'ewqw' => Carbon::now(),
            'Jqwker' => randomStr(9),
            'c1' => Carbon::now()->toDateTimeString(),
        ]);
        return [
            'r' => $user,
        ];
    }

    //更新全部行
    public function modelUpdateAll(){
        $bool = DashboardUser::updateAll([
            'username'=>'Bob',
        ],[
            'fewqw' => randomStr(12),
            'ewqw' => 33333,
        ]);
        return [
            'r' => $bool,
        ];
    }

    //删除全部行
    public function modelDeleteAll(){
        $bool = DashboardUser::deleteAll([
            'username'=>'Bob',
        ]);
        return [
            'r' => $bool,
        ];
    }


    public function debug(){
        $methodDebug = $this->request->input("methodDebug");
        if (empty($methodDebug) || !method_exists($this,$methodDebug)){
            return "Debug method [$methodDebug] not exists";
        }

        return $this->$methodDebug();
    }
}

```


### Eloquent已经实现自增create(..)，代码如下

```php

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
        $poolName = $this->mongoDb->getPool();
        //保存系统全部表自增id的集合名称
        $systemIdCol =  config("mongodb.{$poolName}.options.id_collector");
        $systemIdCol = $systemIdCol?:'systemIds';
        $incrInfo =  $this->mongoDb->findandmodify($systemIdCol,['collection'=>$this->collection],['$inc'=>['maxId'=>1]]);
        if (!isset($incrInfo->value->maxId)){
            throw new IDIncreaseException("Fail to increase and get new id for collection {$this->collection} ");
        }
        return $incrInfo->value->maxId;
    }

}
```

- 维护其他所有集合的自增id的集合(systemIds)数据结构如下
```json
[
  {
    "_id": {
      "$oid": "650b1540a793617afb325f9f"
    },
    "collection": "user",
    "maxId": 9
  },
  {
    "_id": {
      "$oid": "650b1b43a793617afb3262c7"
    },
    "collection": "dashboard_user",
    "maxId": 15
  }
]

```



### Command，执行更复杂的mongo命令

**sql** 和 **mongodb** 关系对比图

|   SQL  | MongoDb |
| --- | --- |
|   WHERE  |  $match (match里面可以用and，or，以及逻辑判断，但是好像不能用where)  |
|   GROUP BY  | $group  |
|   HAVING  |  $match |
|   SELECT  |  $project  |
|   ORDER BY  |  $sort |
|   LIMIT  |  $limit |
|   SUM()  |  $sum |
|   COUNT()  |  $sum |

```php

$pipeline= [
            [
                '$match' => $where
            ], [
                '$group' => [
                    '_id' => [],
                    'groupCount' => [
                        '$sum' => '$groupCount'
                    ]
                ]
            ], [
                '$project' => [
                    'groupCount' => '$groupCount',
                    '_id' => 0
                ]
            ]
];

$count = $this->$mongoDbClient->command('fans', $pipeline);
```