dbroute 是一个支持mysql数据库分库分表的数据库操作中间件

主要功能：

1、支持同一库中事务操作（多个逻辑库事务支持,逻辑表与未分表的表事务支持）.<br>
2、支持读写分离,读库可配置为多个,用逗号分隔.<br>
3、支持事务中的 select 查询走主库查询，以避免事务中查询延时.<br>
4、支持逻辑列的in查询.<br>
5、sql语句对开发者透明.<br>
6、支持同一库中的join查询，但不建议使用.<br>
7、php操作myql方式可配置为mysqli和PDO,推荐mysqli操作方式，只需修改配置常量即可实现操作方式切换，无需修改业务代码<br>
8、对于分库分表的表，sql语句中书写时注意逻辑表名和逻辑列名，详见config.php(配置)和OrderModel.php的示例程序.<br>
9、支持针对分表的逻辑表的所有库表查询，如订单分成1024个表，此方法将查询1024个表后，合并结果集，再返回结果,不建议使用.
