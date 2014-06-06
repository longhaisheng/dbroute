/***********创建分库分表脚本*****************/
DELIMITER $$

DROP PROCEDURE IF EXISTS `seperateDb`$$

CREATE PROCEDURE `seperateDb`()
  BEGIN

    DECLARE Db_Prefix VARCHAR(20) DEFAULT 'db_order_';
    DECLARE Table_Prefix VARCHAR(20) DEFAULT 'order_';
    SET @db_num = 8;
/**数据库数目**/
    SET @table_num = 128;
/**每个库里的表数目**/
    SET @db_count = 0;
    SET @table_count = 0;
    SET @i = 10000;
    SET @j = 10000;


    WHILE @db_count < @db_num DO
      SET @x = RIGHT(@i, 4);
      SET @createDbSql = CONCAT(
          'CREATE DATABASE ', Db_Prefix, @x, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci'
      );
      PREPARE stml FROM @createDbSql;
      EXECUTE stml;

      WHILE @table_count < @table_num DO
        SET @y = RIGHT(@j, 4);
        SET @createTableSql = CONCAT('
    create table ', Db_Prefix, @x, '.', Table_Prefix, @y, ' (
	id int(11) PRIMARY KEY,
	order_sn varchar(10),
	user_id int(11),
	add_time datetime,
	modify_time datetime
    )
');
        PREPARE stmt FROM @createTableSql;
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
CALL seperateDb();

/*****************分库分表结束***********************/

/***********以下是创建序列的表，在上面创建的第一个库里执行以下脚本*****************/

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