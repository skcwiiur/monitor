常见用法

查询
```PHP
$query = $database->select($table, 'instance')
        ->fields('instance')
        ->condition('instance.id', $id);
$query->condition('instance.state', -1, '<>');                                                                                                                                                                                                                                                                                                                                           
$instance = $query->execute()->fetchAssoc();
```

```
$query = $database->select('table', 't');
    $query->fields('t');
    $query->condition('device_id', $device_id);
    $query->condition('is_active', 1);
    $query->orderBy('t.sort', 'ASC');// SORT 1234
$result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
       
```

更新
```
    $query = $database->update($table);
      $query->fields($fields);
      $query->condition('id', $idArr, 'IN');
      $query->execute();
```

删除
```PHP
$database->delete($table)
        ->condition('id', $id)
        ->execute();
        
```

```
   $database->delete($table)
        ->condition('id', $idArr, 'IN')
        ->execute();
```

新增
```
        $insertFields = [
          'device_id' => $device_id,
          'package_id' => '222',
          'conf' => $conf
        ];
        $query = $database->insert('device_conf');
        $query->fields(array_keys($insertFields))->values(array_values($insertFields));
      $query->execute();
```