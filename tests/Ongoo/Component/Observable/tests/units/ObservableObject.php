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
                ->and($triggered = false)
                ->and($o->on('event', $callback))
                ->then
                ->boolean($triggered)->isFalse()
                ->if($o->trigger('event'))
                ->then
                ->boolean($triggered)->isTrue()
        ;

        $this->assert('shutdown observer & trigger event')
                ->if($o = new O())
                ->and($triggered = false)
                ->and($o->on('event', $callback))
                ->then
                ->boolean($triggered)->isFalse()
                ->if($o->off('event', $callback))
                ->and($o->trigger('event'))
                ->then
                ->boolean($triggered)->isFalse()
        ;
    }
    
    public function testMultiEvent()
    {
        $triggered = 0;
        $callback = function($bit) use(&$triggered) {
            return function() use(&$triggered, $bit)
            {
                $triggered = $triggered | $bit;
            };
        };

        $this->assert('trigger multi-event')
                ->if($o = new O())
                ->and($triggered = 0)
                ->and($o->on('event1', $callback(1)))
                ->and($o->on('event2', $callback(2)))
                ->and($o->on('event3', $callback(4)))
                ->then
                ->integer($triggered)->isEqualTo(0)
                ->if($o->trigger('event1'))
                ->if($o->trigger('event2'))
                ->then
                ->integer($triggered)->isEqualTo(3)
                ->if($o->trigger('event3'))
                ->and($o->trigger('event-not-watched'))
                ->then
                ->integer($triggered)->isEqualTo(7)
        ;
    }
    
    public function testCatchAll()
    {
        $triggered = 0;
        $callback = function($bit) use(&$triggered) {
            return function() use(&$triggered, $bit)
            {
                $triggered = $triggered | $bit;
            };
        };
        
        $catchAll = [];
        $callbackCatchAll = function($eventName) use(&$catchAll)
        {
            if( !isset($catchAll[$eventName]) )
            {
                $catchAll[$eventName] = 0;
            }
            $catchAll[$eventName] += 1;
        };

        $this->assert('trigger catch all')
                ->if($o = new O())
                ->and($triggered = 0)
                ->and($o->on('event1', $callback(1)))
                ->and($o->on('event2', $callback(2)))
                ->and($o->on('event3', $callback(4)))
                ->and($o->on(null, $callbackCatchAll))
                ->then
                    ->integer($triggered)->isEqualTo(0)
                    ->array($catchAll)->isEmpty()
                ->if($o->trigger('event1'))
                ->if($o->trigger('event2'))
                ->then
                    ->integer($triggered)->isEqualTo(3)
                    ->array($catchAll)->hasSize(2)
                ->if($o->trigger('event3'))
                ->and($o->trigger('event-not-watched'))
                ->then
                    ->integer($triggered)->isEqualTo(7)
                    ->array($catchAll)->hasSize(4)
                ->if($o->trigger('event3'))
                ->then
                    ->integer($triggered)->isEqualTo(7)
                    ->array($catchAll)->hasSize(4)
                    ->array($catchAll)->integer['event3']->isEqualTo(2)
        ;
    }
    
    public function testOff()
    {
        $triggered = 0;
        $callback = function($bit) use(&$triggered) {
            return function() use(&$triggered, $bit)
            {
                $triggered = $triggered | $bit;
            };
        };
        
        $this->assert('Remove some callback')
                ->if($o = new O())
                ->and($triggered = 0)
                ->and($o->on('event1', $callback(1)))
                ->and($o->on('event2', $callback(2)))
                ->and($o->on('event3', $callback(4)))
                ->and($o->off('event1'))
                ->and($o->off('event3'))
                ->then
                    ->integer($triggered)->isEqualTo(0)
                ->if($o->trigger('event1'))
                ->and($o->trigger('event2'))
                ->and($o->trigger('event3'))
                ->then
                    ->integer($triggered)->isEqualTo(2)
        ;
        
        $this->assert('Remove some callback')
                ->if($o = new O())
                ->and($triggered = 0)
                ->and($o->on('event1', $callback(1)))
                ->and($o->on('event2', $callback(2)))
                ->and($o->on('event3', $callback(4)))
                ->and($o->off(null))
                ->then
                    ->integer($triggered)->isEqualTo(0)
                ->if($o->trigger('event1'))
                ->and($o->trigger('event2'))
                ->and($o->trigger('event3'))
                ->then
                    ->integer($triggered)->isEqualTo(0)
        ;
    }
    
    public function testTriggerWithValue()
    {
        $triggered = 0;
        $dummyValues = [];
        $callback = function($bit, $dummyValue) use(&$triggered, &$dummyValues)
            {
                $triggered = $triggered | $bit;
                $dummyValues[] = $dummyValue;
            };
        
        $catchAll = [];
        $catchAllValues = [];
        $callbackCatchAll = function($eventName, $value1, $value2) use(&$catchAll, &$catchAllValues)
        {
            if( !isset($catchAll[$eventName]) )
            {
                $catchAll[$eventName] = 0;
                $catchAllValues[$eventName] = [];
            }
            $catchAll[$eventName] += 1;
            $catchAllValues[$eventName][] = ['value1' => $value1, 'value2' => $value2];
        };

        $this->assert('trigger with value')
                ->if($o = new O())
                ->and($triggered = 0)
                ->and($o->on('event1', $callback))
                ->and($o->on('event2', $callback))
                ->and($o->on('event3', $callback))
                ->and($o->on(null, $callbackCatchAll))
                ->then
                    ->integer($triggered)->isEqualTo(0)
                    ->array($catchAll)->isEmpty()
                    ->array($catchAllValues)->isEmpty()
                ->if($o->trigger('event1', 1, 'dummy1'))
                ->if($o->trigger('event2', 2, 'dummy2'))
                ->if($o->trigger('eventX', 4, 'dummyX'))
                ->then
                    ->integer($triggered)->isEqualTo(3)
                    ->array($dummyValues)->hasSize(2)
                    ->array($dummyValues)->contains('dummy1')
                    ->array($dummyValues)->contains('dummy2')
                
                    ->array($catchAll)->hasSize(3)
                    ->array($catchAllValues)->hasSize(3)
                    ->array($catchAllValues)->hasKey('event1')
                    ->array($catchAllValues)->hasKey('event2')
                    ->array($catchAllValues)->hasKey('eventX')
                    ->array($catchAllValues)
                        ->child['event1'](function($child){
                            $child->hasSize(1)
                                    ->child[0](function($child){
                                        $child->hasKey('value1')
                                        ->hasKey('value2')
                                        ->integer['value1']->isEqualTo(1)
                                        ->string['value2']->isEqualTo('dummy1');
                                    })
                            ;
                            
                        })
                        ->child['event2'](function($child){
                            $child->hasSize(1)
                                    ->child[0](function($child){
                                        $child->hasKey('value1')
                                            ->hasKey('value2')
                                            ->integer['value1']->isEqualTo(2)
                                            ->string['value2']->isEqualTo('dummy2');
                                    })
                            ;
                            
                        })
                        ->child['eventX'](function($child){
                            $child->hasSize(1)
                                    ->child[0](function($child){
                                        $child->hasKey('value1')
                                            ->hasKey('value2')
                                            ->integer['value1']->isEqualTo(4)
                                            ->string['value2']->isEqualTo('dummyX');
                                    })
                            ;
                            
                        })
        ;
    }

}
