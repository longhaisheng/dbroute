dbroute 是一个支持mysql数据库分库分表的数据库操作中间件

主要功能：

1、分表算法支持平滑迁移(要先做好数据迁移).分表后支持mysql中存储超大数据量(上亿、十亿、百亿不再是问题)<br>
2、分库分表逻辑字段支持字符串和数值类型（不能有小数），路由规则支持 (id % n)、一致性hash、虚拟节点Hash,具体参见ModHash、ConsistentHash、VirtualHash类实现.<br>
3、同一数据库中事务支持,如果在一个事务过程中有其他数据源,系统将抛出异常<br>
   &nbsp;&nbsp;<b>单库多表：详见<font color=red>OrderAO.php</font></b><br>
   &nbsp;&nbsp;&nbsp;&nbsp;1.1、多个逻辑表事务操作，如order、order_goods都为逻辑表且分表规则相同，都根据user_id分<br>
   &nbsp;&nbsp;&nbsp;&nbsp;1.2、多个逻辑表与多个未分表的表（city）事务支持.<br>
   &nbsp;&nbsp;&nbsp;&nbsp;1.3、多个未分表的表事务操作.<br>
   &nbsp;&nbsp;<b>多库多表：详见<font color=red>RefundAO.php</font></b><br>
   &nbsp;&nbsp;&nbsp;&nbsp;1.4、多个逻辑表事务操作支持，但逻辑表间的逻辑列的值相同，如都根据user_id分表的，那么都要传相同的user_id<br>
4、支持读写分离,读库可配置为多个,用逗号分隔.<br>
5、php操作myql方式可配置为 mysqli 和 PDO ,推荐mysqli操作方式，只需修改配置常量即可实现操作方式切换，无需修改任何业务代码<br>
6、支持事务中的 select 查询走主库查询，以避免事务中查询延时(从库数据同步可能有延时),事务结束后面的查询依然可以走从库查询.<br>
7、唯一数字序列值的生成.<br>
8、所有sql语名都使用cls_dbroute类操作,不论是分表的还是未分表的.<br>
9、支持逻辑列的in查询(支持分表的逻辑表).<br>
10、支持遍历所有库表(分表的逻辑表)，如订单分成1024个表，此方法将查询1024个表后，合并结果集，再返回结果,不建议使用,默认只取前二十条记录.<br>
11、支持同一库中的join查询，但不建议使用.<br>
12、sql语句对开发者透明.<br>
13、对于分库分表的表，sql语句中书写时注意逻辑表名和逻辑列名，详见config.php(配置)和OrderModel.php的示例程序.<br>
14、UserModel是根据用户名的值来分表的，用户名唯一.<br>
15、未来考虑使用zookeeper实现心跳监测.<br>
