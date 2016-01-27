<?php

class Data_mapper {

    private $connection;

    function __construct($connection) {
        $this->connection = $connection;
    }

    function insert($object) {
        $parameters = $this->getObjectAttributes($object);
        if ($parameters != null) {
            $values = [];
            $objects = [];
            $arrays = [];
            foreach ($parameters as $key => $parameter) {
                $parameterGetter = 'get' . $parameter;
                if(gettype($object->$parameterGetter()) == "array"){
                    $arrays[] = $parameter;
                    unset($parameters[$key]);
                    continue;
                } else if (gettype($object->$parameterGetter()) == "object"){
                    $objects[] = $parameter;
                    unset($parameters[$key]);
                    continue;
                }
                $value = utf8_decode($object->$parameterGetter());
                $value = $this->stringQuoter($value);
                $values[] = $value;
            }
            $valueString = implode(", ", $values);
            $paramString = implode(", ", $parameters);
            if (($paramString != "" && $paramString != null) && ($valueString != "" && $valueString != null)) {
                $query = "insert into " . get_class($object) . " (" . $paramString . ") values (" . $valueString . ")";
                echo $query;
                $this->connection->query($query);
                $lastId = $this->connection->lastInsertId();
                if(!empty($arrays)){
                    $this->mapArrays($arrays, $lastId, $object);
                };
                if(!empty($objects)){
                    // TODO : Object Mapper 
                }
            }
        }
    }

    private function getObjectAttributes($object) {
        $class = new ReflectionClass($object);
        $classattributes = $class->getProperties();
        if ($class->isInstantiable()) {
            $params = [];
            foreach ($classattributes as $attribute) {
                $attributename = ucfirst($attribute->getName());
                $getname = 'get' . $attributename;
                if ($attributename != 'Id') {
                    $params[] = $attributename;
                }
            }
            return $params;
        } else {
            return null;
        }
    }
    
    private function mapArrays($arrays, $insertId, $object){
        foreach($arrays as $arrayName){
            $usename = $arrayName;
            $secondReference="";
            $getName = 'get'.$arrayName;
            $array = $object->$getName();
            $type = gettype($array[0]);
            $tablename = get_class($object)."_".$usename;
            if($type == "string"){
                $type = "varchar(255)";
            }
            if(stristr(substr($arrayName, 0, 2), "id")){
                $usename = substr($arrayName, 3);
                $tablename = get_class($object)."_".$usename;
                $type = "int unsigned not null";
                $secondReference = ", constraint fk_".$usename."_".  get_class($object)." foreign key (".$arrayName.") references ".$usename."(id)";   
            }
            $sql = utf8_decode("create table if not exists ".$tablename." (id_".get_class($object)." int unsigned not null, ".$arrayName." ".$type.", "
                    . "constraint fk_".$tablename." foreign key (id_".get_class($object).") references ".get_class($object)."(id) ".$secondReference.");");
            echo $sql;
            $this->connection->query($sql);
            foreach ($array as $item){
                $item = $this->stringQuoter($item);
                $sql = utf8_decode("insert into ".$tablename." (id_".get_class($object).",".$arrayName.") values (".$insertId.",".$item.")");
                $this->connection->query($sql);
            }
        }
    }
    private function mapObject(){
        // TODO : define method to map objects.
    }
    
    private function stringQuoter($value){
        if (gettype($value) == 'string') {
                    $value = "'" . $value . "'";
        }
        return $value;
    }

}
