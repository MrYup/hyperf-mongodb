<?php

namespace Mryup\HyperfMongodb;

use Hyperf\Server\Exception\ServerException;

trait IgnoreNotFoundProperty
{

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name??null;
    }


    /**
     * @param array $attrs
     * @return static
     */
    public static function makeByArray(array $attrs = []){
        $r = new static();
        foreach ($attrs as $key => $value) {
            $r->$key = $value;
        }
        $r->init();
        return $r;
    }

    protected function init(){

    }

    /**
     * @return array
     */
    public function toArray(){
        return (array)(json_decode($this->__toString(),true));
    }

    public function __toString()
    {
        return json_encode($this);
    }

    public function __call(string $name, array $arguments)
    {
        $sub = substr($name,0,3);
        $propertyName = substr($name,3,strlen($name));
        $propertyName = ucwords($propertyName);

        if ($sub === 'get'){
            return $this->$propertyName;
        }
        if ($sub === 'set'){
            $this->$propertyName = $arguments[0]??null;
            return $this;
        }

        throw new ServerException("method {$name} mot found");
    }

}