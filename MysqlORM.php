<?php

class MysqlORM
{
    private $sql;
    private $select;
    private $offset;
    private $limit;
    private $table;
    private $order;
    private $sort;
    private $query;

    public function __construct()
    {
        // mysql connect
    }

    private function init() {
        $this->sql = [
            'join' => '',
            'where' => '',
            'group' => '',
            'order' => '',
            'limit' => '',
            'offset' => ''
        ];

        $this->offset = 0;
        $this->limit = 1;
        $this->select = '*';
        $this->table = '';
        $this->order = '';
        $this->sort = '';
        $this->query = '';
    }

    public function table($table) {
        $this->init();
        $this->table = $table;
        return $this;
    }

    public function orderBy($order, $sort = 'DESC') {
        $this->order = $order;
        $this->sort = $sort;
        return $this;
    }

    public function skip($num)
    {
        $this->offset = $num;
        return $this;
    }

    public function select() {
        $this->select = implode(", ", func_get_args());
        return $this;
    }

    // TODO: GroupBy
    public function groupBy()
    {

    }

    // TODO: TABLE JOIN
    public function join()
    {

    }

    public function where($arg1, $arg2, $arg3 = null)
    {
        if ($arg3 === null)
        {
            $this->sql['where'] = $this->mergeWhere("$arg1 = " . $this->transformType($arg2));
        }
        else
        {
            $this->sql['where'] = $this->mergeWhere("$arg1 $arg2 " . $this->transformType($arg3));
        }
        return $this;
    }

    public function whereIn($arg1, $arg2)
    {
        if (gettype($arg2) !== 'array')
        {
            // TODO: throw a error
        }

        $this->sql['where'] = $this->mergeWhere("$arg1 IN (". "'" . implode("', '", $arg2) . "'" .")");

        return $this;
    }

    public function whereRaw($key, $val = [])
    {
        if (gettype($val) !== 'array')
        {
            // TODO: throw a error
        }

        $key = str_replace(' ', '', $key);
        if (count($val) !== substr_count($key, '?'))
        {
            // 缺省个数和参数个数不等
            // TODO: throw a error
        }

        $i = 0;
        $arr = [];

        foreach (explode('and', $key) as $item)
        {
            if (strpos($item, '?'))
            {
                $todo = str_replace('?', $this->transformType($val[$i]), $item);
                $i++;
            }
            else
            {
                $todo = $item;
            }

            $arr[] = $todo;
        }

        $this->sql['where'] = $this->mergeWhere(implode(' AND ', $arr));

        return $this;
    }

    public function insert()
    {
        $keys = '';
        $vals = '';
        foreach (func_get_args()[0] as $key => $val)
        {
            $keys .= $key . ', ';
            $vals .= $this->transformType($val) . ', ';
        }
        $keys = rtrim($keys, ', ');
        $vals = rtrim($vals, ', ');

        $this->query = "INSERT INTO $this->table ($keys) VALUES ($vals)";

        return $this->query;

        mysql_query($this->query);
        return mysql_insert_id();
    }

    public function delete()
    {
        $this->query = "DELETE FROM $this->table " . $this->StringSQL();

        return $this->query;

        return mysql_query($this->query);
    }

    public function update()
    {
        $str = '';

        foreach (func_get_args()[0] as $key => $val)
        {
            $str .= $key . '=' . $this->transformType($val) . ',';
        }

        $str = rtrim($str, ',');

        $this->query = "UPDATE $this->table SET $str " . $this->StringSQL();

        return $this->query;

        return mysql_query($this->query);
    }

    public function first()
    {
        return $this->get(null, false);
    }

    public function count()
    {
        $this->select = 'COUNT(1) as num';

        $this->query = "SELECT $this->select FROM $this->table " . $this->StringSQL();

        $result = mysql_query($this->query);

        if (!$result)
        {
            return false;
        }

        return mysql_fetch_array($result, MYSQL_ASSOC)['num'];
    }

    public function get($limit = null, $isArr = true)
    {
        if (!is_null($limit))
        {
            $this->limit = $limit;
            $this->sql['limit'] = "LIMIT $this->limit";
        }

        if (!empty($this->offset))
        {
            $this->sql['offset'] = "OFFSET $this->offset";
        }

        if (!empty($this->order))
        {
            $this->sql['order'] = "ORDER BY '$this->order' $this->sort";
        }

        $this->query = "SELECT $this->select FROM $this->table " . $this->StringSQL();

        return $this->query;

        $result = mysql_query($this->query);

        if (!$result)
        {
            return false;
        }

        if ($isArr)
        {
            $data = array();
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $data[] = $row;
            }
        }
        else
        {
            $data = mysql_fetch_array($result, MYSQL_ASSOC);
        }

        return $data;
    }

    private function mergeWhere($sql)
    {
        if (empty($this->sql['where']))
        {
            return 'WHERE ' . $sql;
        }
        else
        {
            return 'WHERE ' . $sql . str_replace('WHERE', ' AND', $this->sql['where']);
        }
    }

    private function StringSQL()
    {
        return (implode(' ', $this->sql));
    }

    private function transformType($arg)
    {
        if (gettype($arg) !== 'integer')
        {
            $arg = "'$arg'";
        }

        return $arg;
    }

    public function __destruct()
    {
        // make some log & close DB
    }
}