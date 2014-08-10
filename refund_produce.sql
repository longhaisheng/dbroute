/***********创建分库分表脚本*****************/
DELIMITER $$

DROP PROCEDURE IF EXISTS `refund_multiple_seperate_db`$$

CREATE PROCEDURE `refund_multiple_seperate_db`()
  BEGIN

    DECLARE db_prefix VARCHAR(20) DEFAULT 'sc_refund_';
    DECLARE user_prefix VARCHAR(20) DEFAULT 'refund_';
    DECLARE user_address_prefix VARCHAR(20) DEFAULT 'refund_info_';
    SET @db_num = 4;/**数据库数目**/
    SET @table_num = 16;/**每个库里的表数目**/
    SET @db_count = 0;
    SET @table_count = 0;
    SET @i = 10000;
    SET @j = 10000;


    WHILE @db_count < @db_num DO

      SET @x = RIGHT(@i, 4);
	  if  @db_num=1 then
		SET @db_name=REPLACE(db_prefix, '_', '');
		SET @createDbSql = CONCAT(
          'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ');
	   else
		SET @db_name=CONCAT(db_prefix, @x);
		SET @createDbSql = CONCAT(
		  'CREATE DATABASE ', @db_name, ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
      end if;
      PREPARE stml FROM @createDbSql;
      EXECUTE stml;

      WHILE @table_count < @table_num DO
        SET @y = RIGHT(@j, 4);
        SET @createTableSql = CONCAT('
    create table ', @db_name, '.', user_prefix, @y, ' (
                id int(11) PRIMARY KEY,
                refund_sn varchar(100),
                reason varchar(500),
                user_id int(11),
                add_time datetime,
                modify_time datetime 
                    )
                ');

        SET @create_order_goodsTableSql = CONCAT('
   create table ', @db_name, '.', user_address_prefix, @y, ' (
                id int(11) PRIMARY KEY,
                refund_id int(11),
                goods_id int(11),
                goods_num int(11),
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
CALL refund_multiple_seperate_db();
/*****************分库分表结束***********************/
