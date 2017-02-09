<?php

namespace Ongoo\Component\Observable\tests\units;

use \Ongoo\Component\Observable\ObservableObject as O;

/**
 * Description of ObservableObject
 *
 * @author paul
 */
class ObservableObject extends \mageekguy\atoum\test
{

    public function testTrigger()
    {
        $triggered = false;
        $callback = function() use(&$triggered)
        {
            $triggered = true;
        };

        $this->assert('trigger event')
                ->if($o = new O())
                ->and($o->on('event', $callback))
                ->then
                ->boolean($triggered)->isFalse()
                ->if($o->trigger('event'))
                ->then
                ->boolean($triggered)->isTrue()
        ;

        $this->assert('shutdown observer & trigger event')
                ->if($o = new O())
                ->and($o->on('event', $callback))
                ->then
                ->boolean($triggered)->isFalse()
                ->if($o->off('event', $callback))
                ->and($o->trigger('event'))
                ->then
                ->boolean($triggered)->isFalse()
        ;
    }

}
