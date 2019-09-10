# EVENT 事件库
###
### 注：解决了事件执行顺序，规范了输入输出和异常的处理

### 配置
~~~
$config = [
                \App\Mobile\Event\LoginEvent::class=>[
                   \App\Mobile\Event\Handler\Account\AccountAuthHandler::class=>['priority'=>20,'describe'=>'账户认证处理器'],
                   \App\Mobile\Event\Handler\Account\AccountAuthCodingHandler::class=>['priority'=>20,'describe'=>'账户编号认证处理器'],
                   \App\Mobile\Event\Handler\Account\AccountMenuHandler::class=>['priority'=>80,'describe'=>'账户菜单信息处理器'],
                 \App\Mobile\Event\Handler\Account\AccountStorageHandler::class=>['priority'=>100,'describe'=>'账户信息存储处理器'],
              ],
              'scene'=>[
                    \App\Mobile\Event\LoginEvent::class=>[
                         'auth'=>[
                               'whiteRule' => [
                                   \App\Mobile\Event\Handler\Account\AccountAuthHandler::class,
                                   \App\Mobile\Event\Handler\Account\AccountAuthCodingHandler::class,
                                ],
                               'blockRule' => [
                                   \App\Mobile\Event\Handler\Account\AccountStorageHandler::class
                                ]
                          ]
                     ]
               ]
            ];
~~~
### 安装
 composer require fcjy/event
