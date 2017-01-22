<?php

require_once __DIR__ . '/MysqlORM.php';

$query = new MysqlORM();

echo $users = $query->table('users')
    ->select('id', 'email', 'name', 'age')
    ->whereIn('name', ['ruby', 'rose'])
    ->where('id', '<>', 6)
    ->whereRaw('age > 20 and age < 25 and sex = 0 and cop = ?', 'B')
    ->orderBy('create_time')
    ->skip(20)
    ->get(10);