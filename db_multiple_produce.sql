/***********创建分库分表脚本*****************/
DELIMITER $$

DROP PROCEDURE IF EXISTS `multiple_seperate_db`$$

CREATE PROCEDURE `multiple_seperate_db`()
  BEGIN

    DECLARE db_prefix VARCHAR(20) DEFAULT 'mmall_';
    DECLARE user_prefix VARCHAR(20) DEFAULT 'user_';
    DECLARE user_address_prefix VARCHAR(20) DEFAULT 'user_address_';
    SET @db_num = 4;
/**数据库数目**/
    SET @table_num = 16;
/**每个库里的表数目**/
    SET @db_count = 0;
    SET @table_count = 0;
    SET @i = 10000;
    SET @j = 10000;


    WHILE @db_count < @db_num DO

    SET @x = RIGHT(@i, 4);
    IF @db_num = 1
    THEN
      SET @db_name = REPLACE(db_prefix, '_', '');
      SET @createDbSql = CONCAT(
          'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ');
    ELSE
      SET @db_name = CONCAT(db_prefix, @x);
      SET @createDbSql = CONCAT(
          'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
    END IF;
    PREPARE stml FROM @createDbSql;
      EXECUTE stml;

    WHILE @table_count < @table_num DO
    SET @y = RIGHT(@j, 4);
    SET @createTableSql = CONCAT('
    create table ', @db_name, '.', user_prefix, @y, ' (
	id int(11) PRIMARY KEY,
	user_name varchar(100),
	pwd varchar(100),
	add_time datetime,
	modify_time datetime
    )
');

    SET @create_order_goodsTableSql = CONCAT('
   create table ', @db_name, '.', user_address_prefix, @y, ' (
	id int(11) PRIMARY KEY,
	home_address varchar(150),
	office_address varchar(150),
	user_id int(11),
	add_time datetime,
	modify_time datetime
    )
');
    PREPARE stmt FROM @createTableSql;
      EXECUTE stmt;
    PREPARE stmt FROM @create_order_goodsTableSql;
      EXECUTE stmt;
    SET @table_count = @table_count + 1;
    SET @j = @j + 1;
    END WHILE;
    SET @table_count = 0;

    SET @db_count = @db_count + 1;
    SET @i = @i + 1;

    END WHILE;
  END$$

DELIMITER ;
CALL multiple_seperate_db();


/*****************分库分表结束***********************/

/***********以下是创建序列的表，在上另一个主库里执行以下脚本*****************/

DELIMITER $$

CREATE TABLE `sequence` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `table_name`   VARCHAR(100) DEFAULT NULL,
  `primary_name` VARCHAR(100) DEFAULT NULL,
  `last_seq`     INT(11) DEFAULT NULL,
  `modify_date`  DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
)
  ENGINE =InnoDB
  AUTO_INCREMENT =9
  DEFAULT CHARSET =utf8$$

DELIMITER $$
  
