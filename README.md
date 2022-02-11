# StackStorage [![](https://poggit.pmmp.io/shield.dl.total/StackStorage)](https://poggit.pmmp.io/p/StackStorage)

Add endless storage that is simple and easy to use  
Please check the Feature tab for details.

# Setup

## Config

### config.yml

```yml
# If you are using mysql, choose whether to initialize the function in the DB at server startup
# It is recommended to disable it only when using the same database on multiple servers
# You need to enable this once to load the plugin and initialize the function.
init_func: true

# You can specify the interval to save the cache in seconds
cache_interval: 1

# Checks storage data when the server starts
problem_auto_solution: true
```

### sql.yml

```yml
database:
  # mysql or sqlite
  type: mysql

    # Edit these settings only if you choose "sqlite".
  sqlite:
    # The file name of the database in the plugin data folder.
  # You can also put an absolute path here.
    file: data.sqlite
  # Edit these settings only if you choose "mysql".
  mysql:
    host: 127.0.0.1
    # Avoid using the "root" user for security reasons.
    username: StackStorage
    password: password
    # Database name
    schema: StackStorage
  # The maximum number of simultaneous SQL queries
  # Recommended: 1 for sqlite, 2 for MySQL. You may want to further increase this value if your MySQL connection is very slow.
  worker-limit: 2
```

### Mysql Setup

```mysql
CREATE DATABASE StackStorage;
CREATE USER StackStorage IDENTIFIED BY 'password';
GRANT ALL on StackStorage.* to StackStorage;
```

# How to use

You can open the storage at `/ stackstorage` or `/st`

# Admin

You can open someone else's storage with the following command Also, if the player is online, you can also open it with
the player's name.

/stackstorage [xuid]

# Feature

Use the familiar chest gui that opens the most while playing with Minecraft to get items in and out infinitely
Enchantments and durability can also be saved
![image](https://github.com/Ree-jp-minecraft/StackStrage/blob/master/image/image1.png)

If you put more than 64 pieces of one item, only 64 pieces will be displayed in the storage, but the amount currently
stored in the storage is written in the item description field. The description field will be automatically deleted when
the player puts it in the inventory, so it will not interfere
![image2](https://github.com/Ree-jp-minecraft/StackStrage/blob/master/image/image2.png)

# Permission

`stackstorage.command.my`  
Permission to open my storage by command  
Default can be used by anyone

`stackstorage.command.user`  
Permission to open all user storage by command  
Default can be used by op

# StackStorage API

Interface  
[IStackStorageAPI](https://github.com/Ree-jp-minecraft/StackStrage/blob/master/src/ree_jp/stackstorage/api/IStackStorageAPI.php)

Document  
https://blog.ree-jp.net/stack-storage-api/

# Note

- Data of versions below 0.1.0 cannot be inherited