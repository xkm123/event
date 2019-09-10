<?php

namespace Fcjy\Event;

abstract class Event
{
    /**
     * 结果失败
     */
    const RESULT_FAIL = 0;
    /**
     * 结果成功
     */
    const RESULT_SUCCESS = 1;
    /**
     * 结果成功并中断
     */
    const RESULT_SUCCESS_ABORT = 2;
    /**
     * 事件描述
     *
     * @var null | string
     */
    private $describe = '';
    /**
     * 事件集合
     *
     * @var array
     */
    private $events = [];
    /**
     * 事件状态
     *
     * @var int
     */
    private $eventResult = self::RESULT_SUCCESS;
    /**
     * 事件结果码
     *
     * @var int|string
     */
    private $eventResultCode = self::RESULT_SUCCESS;
    /**
     * 事件状态信息
     *
     * @var string
     */
    private $eventResultMsg = 'OK';
    /**
     * 异常信息
     *
     * @var null|\Exception
     */
    private $eventException = null;

    /**
     * Event constructor.
     *
     * @param array $data 数据包
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $name = "set" . $this->convertUnderline($key);
            if (method_exists($this, $name)) {
                $this->$name($value);
            }
        }
    }

    /**
     * 下划线转驼峰
     *
     * @param string $str 需转换的字符串
     *
     * @return string
     */
    public function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return ucfirst($str);
    }

    /**
     * 获取事件描述
     *
     * @return string|null
     */
    public function getDescribe(): string
    {
        return $this->describe;
    }

    /**
     * 设置事件描述
     *
     * @param string $describe
     *
     * @return Event
     */
    public function setDescribe(string $describe): Event
    {
        $this->describe = $describe;
        return $this;
    }

    /**
     * 获取执行的事件集合
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * 添加事件
     *
     * @param EventHandler $handler 事件处理器
     * @param Event        $event   事件
     *
     * @return Event
     */
    public function addEvent(EventHandler $handler, Event $event): Event
    {
        $this->events[get_class($handler)] = $event;
        return $this;
    }

    /**
     * 获取事件执行结果
     *
     * @return int
     */
    public function getEventResult(): int
    {
        return $this->eventResult;
    }

    /**
     * 获取事件执行结果
     *
     * @return bool
     */
    public function isEventSuccess(): bool
    {
        return !$this->isException() && $this->eventResult != self::RESULT_FAIL;
    }

    /**
     * 是否异常
     *
     * @return bool
     */
    public function isException(): bool
    {
        return $this->eventException != null;
    }

    /**
     * 设置事件结果
     *
     * @param int $eventResult 事件结果
     *                         RESULT_FAIL=0;
     *                         RESULT_SUCCESS=1;
     *                         sRESULT_SUCCESS_ABORT=2;
     *
     * @return Event
     */
    public function setEventResult(int $eventResult): Event
    {
        if (in_array($eventResult, [0, 1, 2])) {
            $this->eventResult = $eventResult;
        }
        return $this;
    }

    /**
     * 获取事件执行结果描述
     *
     * @return string
     */
    public function getEventResultMsg(): string
    {
        return $this->eventResultMsg;
    }

    /**
     * 设置事件执行结果描述
     *
     * @param string $eventResultMsg
     *
     * @return Event
     */
    public function setEventResultMsg(string $eventResultMsg): Event
    {
        $this->eventResultMsg = $eventResultMsg;
        return $this;
    }

    /**
     * 获取事件结果码
     *
     * @return int|string
     */
    public function getEventResultCode()
    {
        return $this->eventResultCode;
    }

    /**
     * 设置事件结果码
     *
     * @param int|string $eventResultCode
     *
     * @return Event
     */
    public function setEventResultCode($eventResultCode): Event
    {
        $this->eventResultCode = $eventResultCode;
        return $this;
    }

    /**
     * 获取事件异常对象
     *
     * @return \Exception|null
     */
    public function getEventException(): ?\Exception
    {
        return $this->eventException;
    }

    /**
     * 设置事件的异常信息
     *
     * @param \Exception $eventException
     *
     * @return Event
     */
    public function setEventException(\Exception $eventException): Event
    {
        $this->eventException = $eventException;
        return $this;
    }

    /**
     * 拷贝异常事件
     *
     * @param Event $event 事件
     *
     * @return $this
     */
    public function copyEventException($event)
    {
        $this->setEventResult(self::RESULT_FAIL);
        $this->setEventResultCode($event->getEventResultCode());
        $this->setEventResultMsg($event->getEventResultMsg());
        $this->setEventException($event->getEventException());
        return $this;
    }

    /**
     * 设置事件结果失败信息
     *
     * @param string $msg  消息
     * @param int    $code 状态码
     *
     * @return $this
     */
    public function setEventResultFail($msg, $code = self::RESULT_FAIL)
    {
        $this->setEventResult(self::RESULT_FAIL);
        $this->setEventResultCode($code);
        $this->setEventResultMsg($msg);
        return $this;
    }

    /**
     *  转换数组
     *
     * @return array
     */
    public function toArray()
    {
        $result = ['event_class' => get_class($this), 'describe' => $this->describe,
            'event_result' => $this->eventResult, 'event_result_msg' => $this->eventResultMsg, 'exception' => null];
        if ($this->eventException != null) {
            $result['exception'] = $this->eventException->getTrace();
        }
        return $result;
    }

    /**
     * 打印调试
     *
     * @return array
     */
    public function dump()
    {
        $result = [];
        foreach ($this->events as $handler => $event) {
            $result[$handler] = $event->toArray();
        }
        return $result;
    }

}