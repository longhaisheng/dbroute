dbroute 是一个支持mysql数据库分库分表的数据库操作中间件

主要功能：

1、分库分表支持：单库多表、多库多表、多库单表(每库一表).<br>
2、分库规则支持 (mod hash)、一致性hash、虚拟节点Hash及日期分库(year|month|day|week),逻辑字段支持字符串和整型.<br>
3、分表规则支持:取模、日期格式分表(year|year_and_month|month|day|week|year_month_day|month_and_day).<br>
4、支持mysql本地事务(本地事务中超过一个数据源抛出异常)及分布式事务.<br>
4、支持事务中的 select 走主库查询，以避免事务中查询延时(从库数据同步可能有延时),事务结束之后的查询依然走从库查询.<br>
5、唯一数值型主键生成策略,建立一个表专门用于生成id(将生成的主键放至本地缓存中).<br>
6、支持读写分离,读库可配置为多个,用逗号分隔,读库支持权重,算法为随机和轮询.<br>
7、php操作myql方式可配置为 mysqli 和 PDO ,默认mysqli操作方式，零切换代价，无需修改任何业务代码.<br>
8、一次web请求中,只要数据库名相同,都使用同一个数据库连接,同一库读写分离除外.<br>
9、连接查询支持一个逻辑表同多个实体表,但不见意使用.<br>
10、所有sql语名都使用cls_dbroute类操作,不论是分表的还是未分表的.<br>
11、支持逻辑列的in查询(支持分表的逻辑表,不包含按日期分表),逻辑列>=、<=、<、>、between...and 在遍历所有库表方法中支持,其他方法暂不支持.<br>
12、支持遍历所有库表(支持分表的逻辑表)的分页查询，如订单分成1024(16库 X 64表)个表，此方法将建立16个数据库连接并查询1024个表,取每个表的前20(默认20)条，之后合并结果集，再返回最终结果集20条数据,支持单个字段排序,不建议使用此方法.<br>
13、sql语句对开发者透明.<br>
14、支持mysql master-master及master-slave架构.<br>
15、数据库IP可使用代理，也可不使用代理,不建议使用代理IP,代理IP有一定的性能损耗.<br>
16、使用zookeeper动态管理配置,并实现主从库的动态切换，(数据库宕机,php端自动更新配置文件并可发邮件及短信通知).<br>
17、dbroute配置管理详见：https://github.com/longhaisheng/dbroute-configserver

注：未经本人同意,请勿作商业用途,QQ:87188524
