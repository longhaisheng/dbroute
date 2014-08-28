dbroute 是一个支持mysql数据库分库分表的数据库操作中间件

主要功能：

1、数据分布单库多表、多库多表、多库单表(每库一个表).<br>
2、分库规则支持 (id % n)、一致性hash、虚拟节点Hash,逻辑字段支持字符串和数值类型（不能有小数）.<br>
3、支持基于单库的事务，但不支持跨库进行事务,如果在一个事务过程中有其他数据源,系统将抛出异常.<br>
4、支持事务中的 select 走主库查询，以避免事务中查询延时(从库数据同步可能有延时),事务结束之后的查询依然走从库查询.<br>
5、唯一数值型主键生成策略,建立一个表专门用于生成id(将生成的主键放至本地缓存中).<br>
6、支持读写分离,读库可配置为多个,用逗号分隔,读库支持权重.<br>
7、php操作myql方式可配置为 mysqli 和 PDO ,默认mysqli操作方式，零切换代价，无需修改任何业务代码.<br>
8、单个请求页面中的代码,只要数据库名相同,都使用同一个数据库连接.<br>
9、连接查询支持一个逻辑表同多个实体表,但不见意使用.<br>
10、所有sql语名都使用cls_dbroute类操作,不论是分表的还是未分表的.<br>
11、支持逻辑列的in查询(支持分表的逻辑表,不包含按日期分表).<br>
12、支持遍历所有库表(分表的逻辑表,不包含按日期分表)的分页查询，如订单分成1024个表，此方法将查询1024个表后，合并结果集，再返回结果,不建议使用,默认只取前二十条记录.<br>
13、sql语句对开发者透明.<br>
14、未来考虑使用zookeeper实现心跳监测.<br>
