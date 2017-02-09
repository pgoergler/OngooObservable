<?php

namespace Ongoo\Component\Observable;

/**
 * Description of Observable
 *
 * @author paul
 */
class ObservableObject implements Observable
{

    protected $observers;

    public function __construct()
    {
        $this->observers = array();
    }

    public function one($event, callable $closure)
    {
        return $this->on($event, $closure, 1);
    }

    public function on($event, callable $closure, $nbMaxExecutions = null)
    {
        if (!isset($this->observers[$event]))
        {
            $this->observers[$event] = new \SplObjectStorage();
        }
        $this->observers[$event]->attach($closure, $nbMaxExecutions);
        return $this;
    }

    public function off($event, callable $closure = null)
    {
        if (is_null($event))
        {
            $this->observers = array();
        } elseif (is_null($closure))
        {
            if (isset($this->observers[$event]))
            {
                unset($this->observers[$event]);
            }
        } else
        {

            if (isset($this->observers[$event]))
            {
                $this->observers[$event]->detach($closure);
            }
        }
        return $this;
    }

    /**
     * 
     * @param type $event
     * @return \SplObjectStorage
     */
    protected function getStorage($event)
    {
        if (!isset($this->observers[$event]))
        {
            return null;
        }
        return $this->observers[$event];
    }

    public function trigger($event, $data = null)
    {
        $args = func_get_args();
        /**
         * args = [$eventName, $data1, $data2, ...]
         */
        call_user_func_array(array($this, 'triggerEvent'), $args);

        \array_unshift($args, null);
        /**
         * args = [null, $eventName, $data1, $data2, ...]
         */
        call_user_func_array(array($this, 'triggerEvent'), $args);
        return $this;
    }

    protected function triggerEvent($event, $data = null)
    {
        $args = func_get_args();
        array_shift($args);

        $storage = $this->getStorage($event);
        if (!$storage)
        {
            return $this;
        }

        $remove = new \SplObjectStorage();
        foreach ($storage as $callable)
        {
            $limit = $storage[$callable];

            if ($callable instanceof \Closure)
            {
                $fn = $callable->bindTo($this);
            } else
            {
                $fn = $callable;
            }
            call_user_func_array($fn, $args);

            if (!is_null($limit))
            {
                $limit--;
                if ($limit > 0)
                {
                    $storage[$callable] = $limit;
                } else
                {
                    $remove[$callable] = true;
                }
            }
        }
        $storage->removeall($remove);

        return $this;
    }

}
