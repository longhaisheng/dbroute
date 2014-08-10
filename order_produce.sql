/***********创建分库分表脚本*****************/
DELIMITER $$

DROP PROCEDURE IF EXISTS `seperateDb`$$

CREATE PROCEDURE `seperateDb`()
  BEGIN

    DECLARE Db_Prefix VARCHAR(20) DEFAULT 'mmall_';
    DECLARE Table_Prefix VARCHAR(20) DEFAULT 'order_';
    DECLARE Goods_Table_Prefix VARCHAR(20) DEFAULT 'order_goods_';
    SET @db_num = 1;/**数据库数目**/
    SET @table_num = 64;/**每个库里的表数目**/
    SET @db_count = 0;
    SET @table_count = 0;
    SET @i = 10000;
    SET @j = 10000;


    WHILE @db_count < @db_num DO

      SET @x = RIGHT(@i, 4);
	  if  @db_num=1 then
		SET @db_name=REPLACE(Db_Prefix, '_', '');
		SET @createDbSql = CONCAT(
          'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ');
	   else
		SET @db_name=CONCAT(Db_Prefix, @x);
		SET @createDbSql = CONCAT(
		  'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
      end if;
      PREPARE stml FROM @createDbSql;
      EXECUTE stml;

      WHILE @table_count < @table_num DO
        SET @y = RIGHT(@j, 4);
        SET @createTableSql = CONCAT('
    create table ', @db_name, '.', Table_Prefix, @y, ' (
	id int(11) PRIMARY KEY,
	order_sn varchar(10),
	user_id int(11),
	add_time datetime,
	modify_time datetime
    )
');

        SET @create_order_goodsTableSql = CONCAT('
   create table ', @db_name, '.', Goods_Table_Prefix, @y, ' (
	id int(11) PRIMARY KEY,
	order_id int(11),
	goods_id int(11),
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
CALL seperateDb();



/*****************分库分表结束***********************/

/***********以下是创建序列的表，在上面创建的mmall库里执行以下脚本*****************/

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
  
  delimiter $$
  
/***********以下是未分库的表，测试事务使用*****************/
CREATE TABLE `city` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `city_name` varchar(80) DEFAULT NULL COMMENT '省市区名',
  `city_code` varchar(45) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT '父ID',
  `type` int(11) DEFAULT NULL COMMENT '类型',
  `is_delete` int(11) DEFAULT NULL COMMENT '是否删除',
  `gmt_created` datetime DEFAULT NULL COMMENT '创建时间',
  `gmt_modified` datetime DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `index_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8 COMMENT='省市区表'$$

delimiter $$

/***********以下是未分库的表，测试事务使用*****************/
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_name` varchar(45) DEFAULT NULL COMMENT '用户名',
  `password` varchar(45) DEFAULT NULL COMMENT '密码',
  `address` varchar(45) DEFAULT NULL COMMENT '地址',
  `is_delete` int(11) DEFAULT NULL COMMENT '是否删除',
  `gmt_create` datetime DEFAULT NULL COMMENT '创建时间',
  `gmt_modified` datetime DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `index_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8 COMMENT='用户表'$$




