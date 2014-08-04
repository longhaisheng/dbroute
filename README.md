dbroute 是一个支持mysql数据库分库分表的数据库操作中间件

主要功能：

1、支持同一库中事务操作
2、支持读写分离
3、支持事务中的 select 查询走主库查询，以避免查询延时
4、支持逻辑列的in查询
5、对开发者透明
6、支持同一库中的join查询，但不见意使用
7、php操作myql方式可配置为mysqli和PDO,推荐mysqli操作方式
8、对于分库分表的表，sql语句中书写时注意逻辑表名和逻辑列名，详见config.php(配置)和OrderModel.php的示例程序
