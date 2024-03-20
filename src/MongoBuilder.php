<?php
namespace Mryup\HyperfMongodb;

use Mryup\HyperfMongodb\Exception\MongoBuilderException;
use Mryup\HyperfMongodb\MongodbModel;

class MongoBuilder{

    const DESC = -1;

    const ASC = 1;


    private $filters = [];

    private $options = [];

    /**
     * 等值查询
     * @param $column
     * @param $value
     * @return $this
     */
    public function where($column,$value = null){
        if (is_array($column) && is_null($value)){
            foreach ($column as $key => $v){
                if (is_int($key) && is_array($v)){
                    $this->where($v);
                }else{
                    $this->filters[$key] = $v;
                }
            }
        }else{
            $this->filters[$column] = $value;
        }
        return $this;
    }

    /**
     * 字段大于指定值查询
     * @param $column
     * @param $value
     * @param bool $equal
     * @return $this
     */
    public function whereGt($column,$value,bool $equal = false){
        $op = $equal?'$gte':'$gt';
        $this->filters[$column] = [$op=>$value];
        return $this;
    }

    /**
     * 字段小于指定值查询
     * @param $column
     * @param $value
     * @param bool $equal
     * @return $this
     */
    public function whereLt($column,$value,bool $equal = false){
        $op = $equal?'$lte':'$lt';
        $this->filters[$column] = [$op=>$value];
        return $this;
    }

    /**
     * 模糊查询
     * @param $column
     * @param $value
     * @param bool $sensitive    -大小写敏感
     * @return $this
     */
    public function whereLike($column,$value,bool $sensitive = true){
        $opt = ['$regex'=>$value];
        if (!$sensitive){
            $opt['$options'] = 'i';
        }
        $this->filters[$column] = $opt;
        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param bool $sensitive    -大小写敏感
     * @return $this
     */
    public function whereNotLike($column,$value,bool $sensitive = true){
        //db.collection.find({name:{'$regex' : '^((?!string).)*$', '$options' : 'i'}})
        $opt = ['$regex'=>"^((?!$value).)*$"];
        if (!$sensitive){
            $opt['$options'] = 'i';
        }
        $this->filters[$column] = $opt;
        return $this;
    }

    /**
     * in查询
     * @param $column
     * @param mixed $values
     * @param bool $not
     * @return $this
     * @throws MongoBuilderException
     */
    public function whereIn($column, $values,bool $not = false){
        if ($values instanceof \Closure){
            $_value = $values();
        }else if (is_object($values) && method_exists($values,'toArray')){
            $_value = $values->toArray();
        }else if(is_array($values)){
            $_value = $values;
        }else{
            throw new MongoBuilderException("Unknown type of values");
        }

        $op = $not?'$nin':'$in';
        $this->filters[$column] = [$op=>$_value];
        return $this;

    }

    /**
     * not in 查询
     * @param $column
     * @param $values
     * @return $this
     * @throws MongoBuilderException
     */
    public function whereNotIn($column, $values){
         $this->whereIn($column, $values,true);
         return $this;
    }

    /**
     * where xxx is null
     * @param $columns
     * @param bool $not
     * @return $this
     */
    public function whereNull($columns,bool $not = false){
        $op = $not?'$ne':'$eq';
        $columns = (array)$columns;
        foreach ($columns as $column) {
            $this->filters[$column] = [$op=>null];
        }
        return $this;
    }

    /**
     * where xxx is not null
     * @param $columns
     * @return void
     */
    public function whereNotNull($columns){
        $this->whereNull($columns,true);
    }

    /**
     * @param $column
     * @param array $values
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, bool $not = false){
        if (!$not){
            $this->filters[$column] = ['$gte'=>$values[0],'$lte'=>$values[1]];
        }else{
            $this->filters['$or'] = [
                [$column=>['$gt'=>$values[1]]],
                [$column=>['$lt'=>$values[0]]]
            ];
        }

        return $this;
    }


    /**
     * @param $column
     * @param array $values
     * @return $this
     */
    public function whereNotBetween($column, array $values){
        $this->whereBetween($column,$values,true);
        return $this;
    }

    public function limit($value){
        $this->options['limit'] = $value;
        return $this;
    }

    public function offset($value){
        $this->options['skip'] = $value;
        return $this;
    }

    /**
     * 设置filter，注意此方法将会覆盖之前已设定的filter
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters){
        $this->filters = $filters;
        return $this;
    }

    /**
     * 添加一个filer
     * @param array $filter
     * @return $this
     */
    public function addFilters(array $filter){
        $this->filters[] = $filter;
        return $this;
    }

    public function select(array $select){
        //查询全部，则不需要指定字段
        if (in_array('*',$select)){
            return $this;
        }
        if (!array_key_exists('projection',$this->options)){
            $this->options['projection'] = [];
        }

        foreach ($select as $value){
            $this->options['projection'][$value] = 1;
        }
        return $this;
    }

    /**
     * 降序
     * @param $column
     * @return $this
     */
    public function orderByDesc($column){
        if (!array_key_exists('sort',$this->options)){
            $this->options['sort'] = [];
        }
        $this->options['sort'][$column] = self::DESC;
        return $this;
    }

    /**
     * 升序
     * @param $column
     * @return $this
     */
    public function orderByASC($column){
        if (!array_key_exists('sort',$this->options)){
            $this->options['sort'] = [];
        }
        $this->options['sort'][$column] = self::ASC;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters(){
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getOptions(){
        return $this->options;
    }

}
