<?php


namespace Fcjy\Event;


abstract class EventHandler
{
    /**
     * 事件优先级
     *
     * @var int
     */
    protected $priority = 100;
    /**
     * 事件描述
     *
     * @var null | string
     */
    protected $describe = '';

    /**
     * EventHandler constructor.
     *
     * @param array $data 数据包
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $name ="set". $this->convertUnderline($key);
            if (method_exists ($this,$name)) {
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
     * 处理事件
     * @param Event $event 事件
     *
     * @return Event
     */
   abstract public function handler($event);

    /**
     * 获取事件优先级
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * 设置事件优先级
     * @param int $priority 优先级
     *
     * @return EventHandler
     */
    public function setPriority(int $priority): EventHandler
    {
        $this->priority = $priority;
        return $this;
    }
    /**
     * 获取事件描述
     * @return string|null
     */
    public function getDescribe(): string
    {
        return $this->describe;
    }

    /**
     * 设置事件描述
     * @param string $describe
     *
     * @return EventHandler
     */
    public function setDescribe(string $describe): EventHandler
    {
        $this->describe = $describe;
        return $this;
    }

    /**
     * 转数组
     * @return array
     */
    public function toArray(){
        return [
            'priority'=>$this->priority,
            'describe'=>$this->describe
        ];
    }
}