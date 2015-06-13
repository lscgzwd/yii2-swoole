/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     2015/5/20 16:07:13                           */
/*==============================================================*/

drop table if exists crm.b_call_history;

drop table if exists crm.b_customer;

drop table if exists crm.b_customer_blacklist;

drop table if exists crm.b_customer_extend;

drop table if exists crm.b_customer_log;

drop table if exists crm.b_follow_done;

drop table if exists crm.b_follow_done_log;

drop table if exists crm.b_follow_todo;

drop table if exists crm.b_follow_todo_log;

drop table if exists crm.b_play;

drop table if exists crm.b_play_stock;

drop table if exists crm.b_play_stock_log;

drop table if exists crm.b_sms_history;

drop table if exists crm.b_user;

drop table if exists crm.b_user_certificate;

drop table if exists crm.b_user_notice;

drop table if exists crm.b_user_play;

drop table if exists crm.b_user_play_invite;

drop table if exists crm.c_user;

drop table if exists crm.c_user_belong_log;

drop table if exists crm.c_user_extend;

drop table if exists crm.c_user_log;

drop table if exists crm.c_user_trade_log;

drop table if exists crm.log_request;

drop table if exists crm.sys_access_log;

drop table if exists crm.sys_article;

drop table if exists crm.sys_attachment;

drop table if exists crm.sys_bweb_access;

drop table if exists crm.sys_bweb_menu;

drop table if exists crm.sys_config;

drop table if exists crm.sys_contract;

drop table if exists crm.sys_contract_log;

drop table if exists crm.sys_contract_template;

drop table if exists crm.sys_order;

drop table if exists crm.sys_role;

drop table if exists crm.sys_role_log;

drop table if exists crm.sys_statistics;

drop table if exists crm.sys_user;

drop table if exists crm.sys_user_role;

drop table if exists crm.sys_user_role_log;

/*==============================================================*/
/* Table: b_call_history                                        */
/*==============================================================*/
create table crm.b_call_history
(
   id                   char(32)  not null comment '主键',
   phone                char(32)  comment '电话',
   content              text  comment '备注',
   attach               char(64)  comment '录音',
   b_user_id            char(32)  not null comment 'b用户id',
   cid                  char(32)  not null comment '客户编号',
   type                 smallint  comment '1 导入 2 手动录入',
   follow_todo_id       char(32)  comment '待跟进ID',
   time                 timestamp  not null default current_timestamp comment '发生时间',
   create_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.b_call_history comment '通话记录';

/*==============================================================*/
/* Table: b_customer                                            */
/*==============================================================*/
create table crm.b_customer
(
   id                   bigint(30)  not null auto_increment comment 'ID',
   bappcid              char(32)  not null default '‘’' comment '客户编号',
   nickname             char(32)  comment '昵称',
   sex                  tinyint(4)  comment '性别',
   birthday             timestamp  not null comment '生日',
   company              char(255)  not null default '' comment '工作单位',
   job                  char(64)  comment '职业',
   truename             char(32)  comment '真实姓名',
   status               smallint  comment '-2 流失（已转走） -1 删除 0 禁用 1 启用 2 未审核',
   trans_id             char(24)  comment '股票证号',
   one_yard_pass        char(32)  comment '一码通账号',
   bid                  char(32)  not null comment '经纪人编号',
   idcard               char(24)  comment '身份证/护照',
   is_bapp_sync         smallint  comment '是否推送',
   is_deleted           smallint,
   is_belong            smallint,
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   update_bid           char(32),
   update_adminid       int,
   fromwhichsys         smallint,
   latest_follow        timestamp  default current_timestamp,
   total_follow         int(10)  default 0,
   total_todo           int(10)  default 0,
   latest_todo          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.b_customer comment 'b客户表 对应Bapp sqlite 客户通讯录表';

/*==============================================================*/
/* Index: ak_broker_id                                          */
/*==============================================================*/
create index ak_broker_id on crm.b_customer
(
   bid
);

/*==============================================================*/
/* Index: index_8                                               */
/*==============================================================*/
create unique index index_8 on crm.b_customer
(
   bappcid
);

/*==============================================================*/
/* Index: index_9                                               */
/*==============================================================*/
create index index_9 on crm.b_customer
(
   status
);

/*==============================================================*/
/* Table: b_customer_blacklist                                  */
/*==============================================================*/
create table crm.b_customer_blacklist
(
   id                   int  not null comment 'ID',
   phone                char(32)  not null comment '电话',
   create_time          timestamp  not null default current_timestamp comment '添加时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.b_customer_blacklist comment 'b客户黑名单';

/*==============================================================*/
/* Table: b_customer_extend                                     */
/*==============================================================*/
create table crm.b_customer_extend
(
   id                   bigint(30)  not null auto_increment comment 'ID',
   cid                  char(32)  not null comment '客户编号',
   type                 smallint  not null comment '联系方式的类型',
   value                char(128)  not null default '' comment '联系方式的内容',
   fromwhichsys         smallint,
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   status               tinyint(4)  not null default 1 comment '状态',
   primary key (id)

);

alter table crm.b_customer_extend comment 'b客户联系方式扩展';

/*==============================================================*/
/* Index: index_17                                              */
/*==============================================================*/
create index index_17 on crm.b_customer_extend
(
   cid
);

/*==============================================================*/
/* Table: b_customer_log                                        */
/*==============================================================*/
create table crm.b_customer_log
(
   id                   char(32)  not null comment '日志ID',
   type                 smallint  not null comment '1 创建 2 修改 3 删除 4 状态 5 转走 流失',
   cid_changed          char(32)  not null comment '被变更的用户',
   b_userid_operation   char(32)  not null comment '操作用户',
   old_information      varchar(10240)  not null comment '早期资料',
   new_information      varchar(10240)  not null comment '变更后资料',
   time                 timestamp  not null default current_timestamp comment '变更时间',
   primary key (id)

);

alter table crm.b_customer_log comment 'b客户日志';

/*==============================================================*/
/* Table: b_follow_done                                         */
/*==============================================================*/
create table crm.b_follow_done
(
   id                   char(32)  not null comment '主键',
   type                 smallint  not null comment '1 电话 2 短信 3 线下其他',
   phone                char(32)  comment '电话',
   content              text  comment '跟进内容',
   attach               char(64)  comment '附件',
   b_userid             char(32)  not null comment '经纪人编号',
   cid                  char(32)  not null comment '客户编号',
   comefrom             smallint  comment '1 导入 2 手动创建  3 系统生成',
   wait_follow_id       char(32)  comment '待跟进ID',
   follow_done_time     timestamp  not null comment '跟进时间',
   is_bapp_sync         smallint  comment '是否推送',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.b_follow_done comment '跟进记录';

/*==============================================================*/
/* Table: b_follow_done_log                                     */
/*==============================================================*/
create table crm.b_follow_done_log
(
   id                   char(32)  not null comment '日志ID',
   `column`             char(32)  not null comment '更改字段名称',
   old_value            text  comment '更改之前的值',
   value                text  comment '更改之后的值',
   b_userid             char(32)  not null comment '经纪人编号',
   cid                  char(32)  not null comment '客户编号',
   follow_done_id       char(32)  comment '待跟进ID',
   action               char(64)  not null comment '操作名 1 创建 2 修改 3 删除 4 状态',
   create_time          timestamp  comment '创建时间',
   primary key (id)

);

alter table crm.b_follow_done_log comment '待跟进记录日志';

/*==============================================================*/
/* Table: b_follow_todo                                         */
/*==============================================================*/
create table crm.b_follow_todo
(
   id                   char(32)  not null comment '主键',
   type                 smallint  not null comment '1 电话跟进 2 短信 3 邮件 4 qq 5 线下 6 其他',
   phone                char(32)  comment '电话',
   content              text  comment '待跟进内容',
   attach               char(64)  comment '附件',
   b_userid             char(32)  not null comment '经纪人编号',
   cid                  char(32)  not null comment '客户编号',
   comefrom             smallint  not null comment '来源1 系统产生 2 用户添加',
   wait_follow_id       char(32)  comment '跟进记录ID',
   place                text,
   status               smallint  comment '1 待处理 2 已处理 0 过期 -1 删除',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   notice_time          timestamp  not null default current_timestamp comment '提醒时间',
   primary key (id)

);

alter table crm.b_follow_todo comment '待跟进';

/*==============================================================*/
/* Table: b_follow_todo_log                                     */
/*==============================================================*/
create table crm.b_follow_todo_log
(
   id                   char(32)  not null comment '日志ID',
   `column`             char(32)  not null comment '更改字段名称',
   old_value            text  comment '更改之前的值',
   value                text  comment '更改之后的值',
   b_userid             char(32)  not null comment '经纪人编号',
   cid                  char(32)  not null comment '客户编号',
   follow_todo_id       char(32)  comment '待跟进ID',
   action               char(64)  not null comment '操作名 1 创建 2 修改 3 删除 4 状态',
   create_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.b_follow_todo_log comment '待跟进记录日志';

/*==============================================================*/
/* Table: b_play                                                */
/*==============================================================*/
create table crm.b_play
(
   id                   int  not null comment 'ID',
   name                 char(64)  not null comment '组合名',
   description          text  comment '组合描述',
   b_userid             char(32)  not null comment '创建者',
   stock                smallint  not null comment '推荐组合',
   status               smallint  not null comment '1 正常 -1 删除 -2 未通过审核 0 禁用 2 未审核',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.b_play comment '投顾组合';

/*==============================================================*/
/* Table: b_play_stock                                          */
/*==============================================================*/
create table crm.b_play_stock
(
   id                   int  not null comment 'ID',
   stock_code           char(32)  not null comment '股票编号',
   play_id              int  not null comment '组合编号',
   status               smallint  not null comment '-1 删除 -2 退出 0 禁用 1 正常 2 未审核',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.b_play_stock comment '投顾组合股票';

/*==============================================================*/
/* Table: b_play_stock_log                                      */
/*==============================================================*/
create table crm.b_play_stock_log
(
   id                   int  not null comment 'ID',
   play_stock_id        int  not null comment '组合编号',
   stock_code           char(32)  not null comment '股票编号',
   type                 smallint  not null comment '1增加 2移除',
   time                 timestamp  not null default current_timestamp comment '发生时间',
   primary key (id)

);

alter table crm.b_play_stock_log comment '投顾组合股票日志';

/*==============================================================*/
/* Table: b_sms_history                                         */
/*==============================================================*/
create table crm.b_sms_history
(
   id                   char(32)  not null comment '主键',
   phone                char(32)  not null comment '电话',
   content              text  not null comment '短信内容',
   b_userid             char(32)  not null comment '经纪人编号',
   cid                  char(32)  not null comment '客户编号',
   comefrom             smallint  not null comment '1 导入 2 手动创建',
   wait_follow_id       char(32)  comment '待跟进ID',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.b_sms_history comment '短信记录';

/*==============================================================*/
/* Table: b_user                                                */
/*==============================================================*/
create table crm.b_user
(
   id                   int(10)  not null auto_increment comment 'ID',
   bid                  char(32)  not null comment '经纪人编号',
   name                 char(32)  not null comment '姓名',
   sex                  tinyint(4)  not null default 0 comment '性别',
   nationalism          char(64)  not null default '' comment '民族',
   birthday             datetime  comment '出生日期',
   validity_start       datetime  comment '身份证有效期开始',
   validity_end         datetime  comment '身份证有效期结束',
   issuing_authority    char(128)  not null comment '签发机关',
   identity_no         char(18)  not null default '' comment '身份证号',
   idcard_address       char(255)  not null default '' comment '身份证地址',
   post_code            char(8)  not null default '' comment '邮编',
   reason               varchar(512)  not null default '' comment '审核原因',
   education            char(64)  not null default '' comment '学历',
   avater               char(64)  not null default '' comment '头像',
   `desc`               text  not null default '' comment '介绍',
   mobilephone          bigint(12)  not null default 0 comment '手机',
   brokerage            float(10)  not null default 0.0 comment '佣金比',
   password             char(32)  not null default '' comment '密码',
   is_certificate       tinyint(4)  not null default 0 comment '当前是否有执业证',
   come_from            smallint(6)  default 0 comment '来源',
   job                  char(32)  not null default '' comment '职业',
   status               tinyint(4)  not null default 1 comment '状态',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   check_state          char(32),
   sac_id               char(32)  not null default '',
   sac_apply_count      char(32),
   sac_apply_password   char(32),
   contract_state       char(32),
   safety_mail          char(64)  not null default '' comment '安全邮箱',
   safety_phone         bigint(20)  not null default 0 comment '安全手机',
   mail                 char(64)  not null default '' comment '个人邮箱',
   address              char(255)  not null default '' comment '联系地址',
   last_upload_client   char(32)  not null default '' comment '上次上传数据的设备码',
   last_sync_client     char(32)  not null default '' comment '上次下载设备编码',
   primary key (id)

);

alter table crm.b_user comment '经纪人认证信息表，认证时的身份证，成绩单等信息';

/*==============================================================*/
/* Index: index_1                                               */
/*==============================================================*/
create unique index index_1 on crm.b_user
(
   bid
);

/*==============================================================*/
/* Index: index_10                                              */
/*==============================================================*/
create index index_10 on crm.b_user
(
   mobilephone
);

/*==============================================================*/
/* Index: index_11                                              */
/*==============================================================*/
create index index_11 on crm.b_user
(
   status
);

/*==============================================================*/
/* Index: index_12                                              */
/*==============================================================*/
create index index_12 on crm.b_user
(
   create_time
);

/*==============================================================*/
/* Index: index_13                                              */
/*==============================================================*/
create index index_13 on crm.b_user
(
   create_time
);

/*==============================================================*/
/* Table: b_user_certificate                                    */
/*==============================================================*/
create table crm.b_user_certificate
(
   id                   int(10)  not null auto_increment comment 'ID',
   bid                  char(32)  not null comment '经纪人编号',
   status               smallint  not null comment '状态 0无效 1有效',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   cer_type             tinyint(4)  not null comment '证件类型',
   attach_id            char(32)  not null comment '证件附件在附件表中的ID',
   primary key (id)

);

alter table crm.b_user_certificate comment 'Bapp中经纪人上传的所有证件附件存档表';

/*==============================================================*/
/* Table: b_user_notice                                         */
/*==============================================================*/
create table crm.b_user_notice
(
   id                   char(32)  not null comment 'ID',
   bid                  char(32)  not null comment '姓名',
   status               smallint  not null comment '状态',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  default current_timestamp on update current_timestamp,
   isread               smallint,
   is_deleted           smallint,
   content              text,
   `desc`               varchar(512),
   notice_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.b_user_notice comment 'Bapp中“我的”经纪人接收各种通知的地方
概要内容是显示在通知列表的内容，全文指通知的富文本全文；';

/*==============================================================*/
/* Table: b_user_play                                           */
/*==============================================================*/
create table crm.b_user_play
(
   id                   int  not null comment 'ID',
   type                 smallint  not null comment '用户类型',
   c_user_id            char(32)  not null comment '用户编号',
   play_id              int  not null comment '组合编号',
   join_time            timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   status               smallint  not null comment '1 正常 0 禁止 -1 离开 2 未审核',
   primary key (id)

);

alter table crm.b_user_play comment '投顾组合用户';

/*==============================================================*/
/* Table: b_user_play_invite                                    */
/*==============================================================*/
create table crm.b_user_play_invite
(
   id                   int  not null comment 'ID',
   type                 smallint  not null comment '被邀请用户类型',
   c_user_id            char(32)  not null comment '用户编号',
   invite_user          char(32),
   invite_user_type     smallint,
   play_id              int  not null comment '组合编号',
   join_time            timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   status               smallint  not null comment '1 正常 0 禁止 -1 离开 2 未审核',
   primary key (id)

);

alter table crm.b_user_play_invite comment '投顾组合用户邀请';

/*==============================================================*/
/* Table: c_user                                                */
/*==============================================================*/
create table crm.c_user
(
   id                   int(11)  not null auto_increment comment 'ID',
   cid                  char(32)  not null comment '客户编号',
   bapp_broker_id       char(32)  not null comment '当前Bapp经纪人编号',
   bapp_broker_time     timestamp,
   password             char(32)  not null default '' comment '密码',
   mobilephone          bigint(12)  not null comment '登录手机号',
   avater               char(32)  not null default '' comment '头像',
   nickname             char(32)  not null default '' comment '昵称',
   sex                  tinyint(4)  not null default 0 comment '性别 1 男 2 女',
   birthday             timestamp  not null comment '生日',
   company              char(255)  not null default '',
   job                  char(64)  not null default '' comment '职业',
   truename             char(32)  not null default '' comment '真实姓名',
   status               tinyint(4)  not null default 1 comment '-2 流失（已转走） -1 删除 1 未注册 2 未开户 3 开户中 4 正常 5 禁用',
   trans_id             char(24)  not null default '' comment '股票证号',
   one_yard_pass        char(32)  not null default '' comment '一码通账号',
   idcard               char(24)  not null default '' comment '身份证/护照',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   last_login           timestamp  not null default current_timestamp comment '登录时间',
   type                 tinyint(4)  default 1 comment '1股民 2股神',
   join_play_count      smallint(6)  not null default 0 comment '用户加入组合数量',
   create_play_count    smallint(6)  not null default 0 comment '用户创建的组合数量',
   primary key (id)

);

alter table crm.c_user comment 'c用户';

/*==============================================================*/
/* Index: index_3                                               */
/*==============================================================*/
create index index_3 on crm.c_user
(
   bapp_broker_id
);

/*==============================================================*/
/* Index: index_2                                               */
/*==============================================================*/
create index index_2 on crm.c_user
(
   cid
);

/*==============================================================*/
/* Index: index_5                                               */
/*==============================================================*/
create index index_5 on crm.c_user
(
   status
);

/*==============================================================*/
/* Index: index_4                                               */
/*==============================================================*/
create index index_4 on crm.c_user
(
   mobilephone
);

/*==============================================================*/
/* Table: c_user_belong_log                                     */
/*==============================================================*/
create table crm.c_user_belong_log
(
   id                   char(32)  not null comment '日志ID',
   c_user_id            int(10)  not null default 0,
   b_cid                char(32)  not null,
   uid_operation        char(32)  not null comment '操作用户',
   old_broker           char(32)  not null comment '早期经纪人user_id',
   current_broker       char(32)  not null comment '当前经纪人user_id',
   time                 timestamp  not null default current_timestamp comment '变更时间',
   reason               varchar(512)  not null comment '变更理由',
   primary key (id)

);

alter table crm.c_user_belong_log comment 'c用户归属变更日志';

/*==============================================================*/
/* Table: c_user_extend                                         */
/*==============================================================*/
create table crm.c_user_extend
(
   id                   int(11)  not null auto_increment comment 'ID',
   cid                  char(32)  not null comment '客户编号',
   type                 smallint(6)  not null comment '联系方式的类型',
   value                char(128)  comment '联系方式的内容',
   fromwhichsys         tinyint(4),
   create_time          timestamp  not null default current_timestamp,
   primary key (id)

);

alter table crm.c_user_extend comment 'c客户联系方式扩展';

/*==============================================================*/
/* Index: index_6                                               */
/*==============================================================*/
create index index_6 on crm.c_user_extend
(
   cid
);

/*==============================================================*/
/* Index: index_7                                               */
/*==============================================================*/
create index index_7 on crm.c_user_extend
(
   type
);

/*==============================================================*/
/* Table: c_user_log                                            */
/*==============================================================*/
create table crm.c_user_log
(
   id                   char(32)  not null comment '日志ID',
   type                 tinyint(4)  not null comment '1. 创建 2 修改 3 删除 4 状态更改 5 转走 流失',
   uid_changed          char(32)  not null comment '被变更的用户',
   uid_operation        char(32)  not null comment '操作用户',
   old_information      varchar(10240)  not null comment '早期资料',
   new_information      varchar(10240)  not null comment '变更后资料',
   time                 timestamp  not null default current_timestamp comment '变更时间',
   primary key (id)

);

alter table crm.c_user_log comment 'c用户资料变更日志';

/*==============================================================*/
/* Table: c_user_trade_log                                      */
/*==============================================================*/
create table crm.c_user_trade_log
(
   id                   int  not null comment 'ID',
   c_user_id            int(10)  not null default 0,
   b_user_id            char(32)  not null comment '经纪人编号',
   money                float(10)  not null comment '交易金额',
   size                 int(10)  not null comment '交易手数',
   commission_ratio     float(10)  not null comment '佣金比例',
   commission           float(10)  not null comment '佣金',
   trade_no             char(32)  not null comment '交易号',
   trade_time           timestamp  not null comment '交易时间',
   price                float(10)  not null comment '成交价',
   stock_code           char(32)  not null comment '股票代码',
   create_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.c_user_trade_log comment 'C用户交易记录表';

/*==============================================================*/
/* Table: log_request                                           */
/*==============================================================*/
create table crm.log_request
(
   logid                char(32)  not null comment '日志ID',
   controller           char(64)  not null comment '控制器名',
   action               char(64)  not null comment '操作名',
   request              text  comment '数据',
   type                 smallint  not null comment '用户类型',
   uid                  char(32)  not null comment '用户编号',
   request_begin        int  not null comment '请求开始时间',
   request_end          int  not null comment '请求结束时间',
   status               char(32)  not null comment '响应状态',
   primary key (logid)

);

alter table crm.log_request comment '操作日志';

/*==============================================================*/
/* Table: sys_access_log                                        */
/*==============================================================*/
create table crm.sys_access_log
(
   logid                char(32)  not null comment '日志ID',
   uid_changed          char(32)  not null comment '被变更的用户',
   uid_operation        char(32)  not null comment '操作用户',
   old_permission       varchar(10240)  not null comment '早期权限',
   current_permission   varchar(10240)  not null comment '当前权限',
   change_time          timestamp  not null default current_timestamp comment '变更时间',
   primary key (logid)

);

alter table crm.sys_access_log comment '用户权限变更日志
';

/*==============================================================*/
/* Table: sys_article                                           */
/*==============================================================*/
create table crm.sys_article
(
   id                   int  not null comment 'ID',
   title                char(128)  not null comment '标题',
   description          varchar(512)  comment '概述',
   content              text  not null comment '内容',
   thumb                char(64)  comment '缩略图',
   user_type            smallint  comment '1 系统管理用户 2 经纪人3客户',
   uid                  char(32)  not null comment '用户编号',
   status               smallint  not null comment '-1 删除 0 禁用 1 启用',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.sys_article comment '资讯表';

/*==============================================================*/
/* Table: sys_attachment                                        */
/*==============================================================*/
create table crm.sys_attachment
(
   id                   char(32)  not null comment 'ID',
   original_name        char(64)  not null default '' comment '标题',
   current_name         varchar(64)  not null default '' comment '概述',
   bucket               char(32)  not null default '' comment '内容',
   user_type            tinyint(4)  not null comment '1 系统管理用户 2 经纪人3客户',
   user_id              char(32)  not null default '' comment '用户id',
   status               tinyint(4)  not null default 1 comment '-1 删除 0 禁用 1 启用',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.sys_attachment comment '附件表';

/*==============================================================*/
/* Table: sys_bweb_access                                       */
/*==============================================================*/
create table crm.sys_bweb_access
(
   id                   int  not null comment '主键',
   uid                  char(32)  not null comment '用户编号',
   menu_id              int  not null comment '菜单编号',
   primary key (id)

);

alter table crm.sys_bweb_access comment '各类用户权限表';

/*==============================================================*/
/* Table: sys_bweb_menu                                         */
/*==============================================================*/
create table crm.sys_bweb_menu
(
   id                   int(10)  not null auto_increment comment 'ID',
   name                 char(32)  not null comment '菜单名',
   pid                  int(10)  not null default 0 comment '父级菜单',
   `order`              int(5)  default 100 comment '排序',
   controller           char(128)  comment '控制器名',
   action               char(128)  comment 'actionID',
   status               tinyint(4)  not null comment '-1 删除 0 禁用1 启用2 启用并隐藏',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.sys_bweb_menu comment '菜单表';

/*==============================================================*/
/* Table: sys_config                                            */
/*==============================================================*/
create table crm.sys_config
(
   `key`                char(64)  not null comment '键',
   value                char(255)  comment '值',
   primary key (`key`)

);

alter table crm.sys_config comment '配置表';

/*==============================================================*/
/* Table: sys_contract                                          */
/*==============================================================*/
create table crm.sys_contract
(
   id                   int(10)  not null auto_increment comment '主键',
   id_contract_template char(32),
   bid                  char(32)  comment '电话',
   contract_name        char(32),
   attach_contract_pic  char(32),
   contract_start_time  timestamp,
   contract_end_time    timestamp,
   contract_state       char(32),
   contract_verson_no   char(32),
   create_people        char(32),
   create_time          timestamp,
   update_time          timestamp,
   primary key (id)

);

alter table crm.sys_contract comment '合同范本管理';

/*==============================================================*/
/* Index: index_15                                              */
/*==============================================================*/
create index index_15 on crm.sys_contract
(
   bid
);

/*==============================================================*/
/* Table: sys_contract_log                                      */
/*==============================================================*/
create table crm.sys_contract_log
(
   id                   int(10)  not null auto_increment comment '主键',
   bid                  char(32)  comment '电话',
   field                char(32),
   original_value       char(64),
   present_value        char(64),
   update_people        char(32),
   create_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.sys_contract_log comment '合同变更日志';

/*==============================================================*/
/* Index: index_16                                              */
/*==============================================================*/
create index index_16 on crm.sys_contract_log
(
   bid
);

/*==============================================================*/
/* Table: sys_contract_template                                 */
/*==============================================================*/
create table crm.sys_contract_template
(
   id                   int(10)  not null auto_increment comment '主键',
   name                 char(32)  comment '电话',
   content              text  comment '备注',
   number_contract_pla  char(32),
   create_people        char(64)  comment '录音',
   create_time          timestamp  default current_timestamp,
   update_time          timestamp  default current_timestamp on update current_timestamp,
   note                 char(64)  not null comment '客户编号',
   status               smallint  comment '1 导入 2 手动录入',
   primary key (id)

);

alter table crm.sys_contract_template comment '合同范本管理';

/*==============================================================*/
/* Table: sys_order                                             */
/*==============================================================*/
create table crm.sys_order
(
   id                   int(10)  not null auto_increment comment '主键',
   bid                  char(32)  comment '电话',
   contract_name        char(32),
   contract_express_from char(32),
   contract_express_unit char(32),
   contract_address     char(32),
   receip_express_name  char(32),
   receip_certificate_unit char(32),
   receip_express_unit  char(32),
   receip_express_no    char(32),
   primary key (id)

);

alter table crm.sys_order comment '快递单号表';

/*==============================================================*/
/* Index: index_14                                              */
/*==============================================================*/
create index index_14 on crm.sys_order
(
   bid
);

/*==============================================================*/
/* Table: sys_role                                              */
/*==============================================================*/
create table crm.sys_role
(
   id                   int  not null comment 'ID',
   name                 char(32)  not null comment '组织名',
   parent_id            int  not null comment '父组组织',
   description          text  comment '组织描述',
   status               smallint  not null comment '-1 删除 0 禁用 1 正常',
   province             char(32)  not null comment '省/直辖市/自治区',
   city                 char(32)  not null comment '市',
   country              char(32)  not null comment '县',
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   `order`              int(10)  not null default 100 comment '排序',
   primary key (id)

);

alter table crm.sys_role comment '组织表';

/*==============================================================*/
/* Table: sys_role_log                                          */
/*==============================================================*/
create table crm.sys_role_log
(
   id                   int  not null,
   old_role_id          int  not null comment '变更前组织ID',
   current_role_id      int  not null comment '当前组织ID',
   operation_uid        char(32)  not null comment '操作者',
   type                 smallint  comment '操作者类型 管理员， 经纪人，投顾',
   reason               varchar(512)  not null comment '变更的原由',
   time                 timestamp  not null default current_timestamp comment '发生时间',
   primary key (id)

);

alter table crm.sys_role_log comment '组织变更日志';

/*==============================================================*/
/* Table: sys_statistics                                        */
/*==============================================================*/
create table crm.sys_statistics
(
   `key`                char(64)  not null comment '键',
   value                char(255)  comment '值',
   primary key (`key`)

);

alter table crm.sys_statistics comment '统计表';

/*==============================================================*/
/* Table: sys_user                                              */
/*==============================================================*/
create table crm.sys_user
(
   id                   char(32)  not null comment 'ID',
   name                 char(32)  not null comment '姓名',
   mobile               bigint(12)  not null comment '手机',
   avater               char(64)  comment '头像',
   `desc`               text  comment '介绍',
   sex                  smallint  default 0 comment '性别',
   password             char(32)  not null comment '密码',
   idcard               char(24)  comment '身份证/护照',
   idcard_attach        char(64)  comment '证件附件',
   status               smallint  not null comment '-1 删除 0 禁用1正常',
   create_user_id       char(32),
   update_user_id       char(32),
   create_time          timestamp  not null default current_timestamp comment '创建时间',
   update_time          timestamp  not null default current_timestamp on update current_timestamp comment '更新时间',
   primary key (id)

);

alter table crm.sys_user comment '综合管理/营销/运营用户';

/*==============================================================*/
/* Table: sys_user_role                                         */
/*==============================================================*/
create table crm.sys_user_role
(
   id                   int  not null comment 'ID',
   type                 smallint  comment '管理员，经纪人投顾',
   user_id              char(32)  not null comment '用户编号ID',
   role_id              int  not null comment '组织角色编号',
   create_time          timestamp  default current_timestamp,
   primary key (id)

);

alter table crm.sys_user_role comment '用户组织关联表';

/*==============================================================*/
/* Table: sys_user_role_log                                     */
/*==============================================================*/
create table crm.sys_user_role_log
(
   logid                char(32)  not null comment '日志ID',
   uid_changed          char(32)  not null comment '被变更的用户',
   uid_operation        char(32)  not null comment '操作用户',
   old_role             int  not null comment '早期角色',
   new_role             int  not null comment '变更后角色',
   change_time          timestamp  not null default current_timestamp comment '变更时间',
   user_type_changed    smallint  comment '管理员，经纪人/投顾',
   primary key (logid)

);

alter table crm.sys_user_role_log comment '用户组织变更日志';

