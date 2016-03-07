PShard
======
[![Build Status](https://travis-ci.org/iborodikhin/pshard.png?branch=master)](https://travis-ci.org/iborodikhin/pshard)

Shards data between multiple virtual shards and multiple real shards using PDO.

Usage
------

1. Create tables with data:

    ```mysql
    CREATE TABLE real_shard (
        'id' INT(10) NOT NULL AUTO_INCREMENT,
        'name' VARCHAR(50) NOT NULL,
        'dsn' VARCHAR(50) NOT NULL,
        'username' VARCHAR(50) NOT NULL DEFAULT '',
        'password' VARCHAR(200) NOT NULL DEFAULT '',
        'options' VARCHAR(1000) NOT NULL DEFAULT '',
        PRIMARY KEY ('id'),
        UNIQUE KEY 'name' ('name')
    ) ENGINE=InnoDB;
    INSERT INTO real_shard VALUES (NULL, 'real_shard1', 'mysql:host=1.2.3.4;dbname=shard', 'user', 'pAsSwOrD', '{json_encoded_options_list}');

    CREATE TABLE virtual_shard (
        'id' INT(10) NOT NULL AUTO_INCREMENT,
        'virtual' VARCHAR(50) NOT NULL,
        'real' VARCHAR(50) NOT NULL,
        PRIMARY KEY ('id),
        UNIQUE KEY 'virtual_real' ('virtual', 'real')
    ) ENGINE=InnoDB;
    INSERT INTO virtual_shard VALUES (NULL, 'virtual_shard1', 'real_shard1');
    INSERT INTO virtual_shard VALUES (NULL, 'virtual_shard2', 'real_shard1');
    ...
    INSERT INTO virtual_shard VALUES (NULL, 'virtual_shardN', 'real_shard1');

    ```

2. Create PDO instance for database containing shards map:

    ```php
    <?php
    $shardMap = new \PDO(
        $dsn,
        $username,
        $password,
        $options
    );

    ```

3. Create PShard instance and use it for queries:

    ```php
    <?php
    $pShard = new \PShard\PShard($shardMap);
    $statement = $pShard->getConnectionForKey($user['mail'])
        ->prepare('INSERT INTO users (name, mail) VALUES (:name, :mail)');
    $statement->bindValue('name', $user['name'], \PDO::PARAM_STR);
    $statement->bindValue('mail', $user['mail'], \PDO::PARAM_STR);
    $statement->execute();

    ```
