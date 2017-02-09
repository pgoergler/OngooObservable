<?php

namespace Ongoo\Component\Observable;

/**
 * Description of Observable
 *
 * @author paul
 */
interface Observable
{

    public function one($event, callable $closure);

    public function on($event, callable $closure, $nbMaxExecutions = null);

    public function off($event, callable $closure = null);

    public function trigger($event, $data = null);
}
