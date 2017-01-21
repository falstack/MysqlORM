<?php

class DB
{
    private $sql;
    private $select;
    private $offset;
    private $limit;
    private $table;
    private $order;
    private $sort;
    private $query;

    // TODO: TABLE JOIN
    // TODO: WHERE IN
    public function __construct()
    {
        // mysql connect
    }

    private function init() {
        $this->sql = [
            'where' => '',
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

    public function order($order, $sort = 'DESC') {
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

    public function where($arg1, $arg2, $arg3 = null)
    {
        // TODO: 目前一条语句只支持一个 where 或 whereRaw
        if ($arg3 === null)
        {
            $this->sql['where'] = "WHERE $arg1 = " . $this->transformType($arg2);
        }
        else
        {
            $this->sql['where'] = "WHERE $arg1 $arg2 " . $this->transformType($arg3);
        }
        return $this;
    }

    public function whereRaw($key, $val = [])
    {
        // TODO: 参数类型校验
        // TODO: 目前一条语句只支持一个 where 或 whereRaw
        $key = str_replace(' ', '', $key);
        if (count($val) !== substr_count($key, '?'))
        {
            // 缺省个数和参数个数不等
            // TODO: throw a error
            return $this;
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

        $this->sql['where'] = 'WHERE ' . implode(' AND ', $arr);

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

        mysql_query($this->query);
        return mysql_insert_id();
    }

    public function delete()
    {
        $this->query = "DELETE FROM $this->table " . $this->StringSQL();

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

        return mysql_query($this->query);
    }

    public function first()
    {
        return $this->get(null, false);
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

$query = new DB();