<?php

namespace Fcjy\Event;

/**
 * 事件驱动
 * Class EventBus
 * User: 623279281@qq.com
 *
 * @package Fcjy\Event
 *
 */
class EventBus
{
    /**
     * 事件驱动
     *
     * @var array
     */
    private static $events = array();
    /**
     * 事件驱动映射图
     *
     * @var array
     */
    private static $eventMap = array();
    /**
     * 配置信息
     *
     * @var array
     * @example
     *        $config = [
     *           \App\Mobile\Event\LoginEvent::class=>[
     *              \App\Mobile\Event\Handler\Account\AccountAuthHandler::class=>['priority'=>20,'describe'=>'账户认证处理器'],
     *              \App\Mobile\Event\Handler\Account\AccountAuthCodingHandler::class=>['priority'=>20,'describe'=>'账户编号认证处理器'],
     *              \App\Mobile\Event\Handler\Account\AccountMenuHandler::class=>['priority'=>80,'describe'=>'账户菜单信息处理器'],
     *              \App\Mobile\Event\Handler\Account\AccountStorageHandler::class=>['priority'=>100,'describe'=>'账户信息存储处理器'],
     *          ],
     *         'scene'=>[
     *               \App\Mobile\Event\LoginEvent::class=>[
     *                    'auth'=>[
     *                          'whiteRule' => [
     *                              \App\Mobile\Event\Handler\Account\AccountAuthHandler::class,
     *                              \App\Mobile\Event\Handler\Account\AccountAuthCodingHandler::class,
     *                           ],
     *                          'blockRule' => [
     *                              \App\Mobile\Event\Handler\Account\AccountStorageHandler::class
     *                           ]
     *                     ]
     *                ]
     *          ]
     *       ];
     *
     */
    private static $config = array();
    /**
     * 场景信息
     *
     * @var array
     */
    private static $scene = array();

    /**
     * 触发一个事件
     *
     * @param Event  $event 事件
     * @param string $scene 场景
     *
     * @return Event|null
     * @throws
     */
    public static function emitScene(Event $event, $scene = '')
    {
        $config = ['whiteRule' => [], 'blockRule' => []];
        if (!empty($scene)) {
            $config = array_merge($config, self::getScene(get_class($event), $scene));
        }
        return self::emit($event, $config['whiteRule'], $config['blockRule']);
    }


    /**
     * 获取场景信息
     *
     * @param string|null $eventName 事件名称
     * @param string|null $scene     场景名称
     *
     * @return array
     */
    public static function getScene($eventName = null, $scene = null)
    {
        if (is_null($eventName)) return self::$scene;
        if (!isset(self::$scene[$eventName])) return [];
        if (is_null($scene)) return self::$scene[$eventName];
        if (!isset(self::$scene[$eventName][$scene])) return [];
        return self::$scene[$eventName][$scene];
    }

    /**
     * 触发一个事件
     *
     * @param Event $event     事件
     * @param array $whiteRule 白名单
     * @param array $blockRule 黑名单
     *
     * @return Event|null
     * @throws
     */
    public static function emit(Event $event, array $whiteRule = [], array $blockRule = [])
    {
        $eventClassName = get_class($event);
        self::importEvent($eventClassName);
        if (!empty(self::$events[$eventClassName])) {
            foreach (self::$events[$eventClassName] as $index => $handler) {
                $handlerClassName = get_class($handler);
                if (in_array($handler, $blockRule)) continue;
                if (!empty($whiteRule) && !in_array($handlerClassName, $whiteRule)) continue;
                try {
                    $event->addEvent($handler, $event);
                    /* @var $handler EventHandler */
                    $event = $handler->handler($event);
                    if (in_array($event->getEventResult(), [0, 2])) {
                        return $event;
                    }
                } catch (\Exception $e) {
                    return $event->setEventResult(Event::RESULT_FAIL)
                        ->setEventResultMsg("执行插件异常" . "(" . $index . "):" . get_class($handler))
                        ->setEventException($e);
                }
            }
        }
        return $event;
    }

    /**
     * 添加观察者
     *
     * @param string       $eventClassName 事件类名
     * @param EventHandler $handler        事件处理器
     * @param bool         $sort           是否排序
     */
    public static function watch(string $eventClassName, EventHandler $handler, bool $sort = true)
    {
        if (!isset(self::$events[$eventClassName])) {
            self::$events[$eventClassName] = array();
        }
        if (!isset(self::$eventMap[$eventClassName])) {
            self::$eventMap[$eventClassName] = array();
        }
        self::$eventMap[$eventClassName][$handler] = $handler->toArray();
        array_push(self::$events[$eventClassName], $handler);
        if ($sort) {
            self::sort($eventClassName);
        }
    }

    /**
     * 排序
     *
     * @param string|null $eventClassName 事件名称
     *
     * @return array
     */
    public static function sort(string $eventClassName = null)
    {
        if (!empty($eventClassName) && self::$events[$eventClassName]) {
            usort(self::$events[$eventClassName], function (EventHandler $h1, EventHandler $h2) {
                return $h1->getPriority() >= $h2->getPriority();
            });
        } else {
            foreach (self::$events as $key => $values) {
                usort(self::$events[$key], function (EventHandler $h1, EventHandler $h2) {
                    return $h1->getPriority() >= $h2->getPriority();
                });
            }
        }
        return self::$events;
    }

    /**
     * 导入配置
     *
     * @param array $events
     */
    public static function import(array $events = [])
    {
        self::$config = $events;
        if (isset(self::$config['scene'])) {
            self::$scene = self::$config['scene'];
            unset(self::$config['scene']);
        }
    }

    /**
     * 导入事件配置
     *
     * @param string $event 事件类名
     */
    private static function importEvent(string $event)
    {
        if (!empty(self::$config[$event])) {
            $hasChange = false;
            $handlers = self::$config[$event];
            foreach ($handlers as $handler => $config) {
                if (!isset(self::$eventMap[$event][$handler])) {
                    if (!isset(self::$eventMap[$event])) {
                        self::$eventMap[$event] = array();
                    }
                    self::$eventMap[$event][$handler] = $config;
                    if (!isset(self::$events[$event])) {
                        self::$events[$event] = array();
                    }
                    array_push(self::$events[$event], new $handler($config));
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                self::sort();
            }
        }
    }

    /**
     * 导出配置
     *
     * @return array
     */
    public static function export()
    {
        $map = self::$eventMap;
        $map['scene'] = self::$scene;
        return $map;
    }
}
