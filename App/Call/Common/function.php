<?php
function getDbInfo(){
  // 数据库配置///
  $databaseInfo['default']['default'] = array (
    'database' => 'call_db',
    'username' => 'root',
    'password' => 'call@feima',
    'prefix' => '',
    'host' => '127.0.0.1',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  );

    if (strpos($_SERVER['HTTP_HOST'], 'gshyj') !== FALSE) {
        $databaseInfo['default']['default'] = array (
            'database' => 'call_db',
            'username' => 'yunqinet',
            'password' => 'yunqi123',
            'prefix' => '',
            'host' => '127.0.0.1',
            'port' => '3306',
            'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
            'driver' => 'mysql',
        );
    }

  return $databaseInfo;
}
\Drupal\Core\Database\Database::setMultipleConnectionInfo(getDbInfo());

/**
 * @param $target
 * @param $key
 * @return \Drupal\Core\Database\Connection
 *   The corresponding connection object.
 */
function getDB($target = 'default', $key = NULL){
  return \Drupal\Core\Database\Database::getConnection($target, $key);
}






