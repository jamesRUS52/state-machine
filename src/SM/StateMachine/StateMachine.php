<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\StateMachine;

use SM\Callback\Callback;
use SM\Callback\CallbackFactory;
use SM\Callback\CallbackFactoryInterface;
use SM\Callback\CallbackInterface;
use SM\Event\SMEvents;
use SM\Event\TransitionEvent;
use SM\SMException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class StateMachine implements StateMachineInterface
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var EventDispatcherInterface|null
     */
    protected $dispatcher;

    /**
     * @var CallbackFactoryInterface
     */
    protected $callbackFactory;

    /**
     * @param object                        $object          Underlying object for the state machine
     * @param array                         $config          Config array of the graph
     * @param EventDispatcherInterface|null $dispatcher      EventDispatcher or null not to dispatch events
     * @param CallbackFactoryInterface|null $callbackFactory CallbackFactory or null to use the default one
     *
     * @throws SMException If object doesn't have configured property path for state
     */
    public function __construct(
        object $object,
        array $config,
        EventDispatcherInterface $dispatcher      = null,
        CallbackFactoryInterface $callbackFactory = null
    ) {
        $this->object          = $object;
        $this->dispatcher      = $dispatcher;
        $this->callbackFactory = $callbackFactory ?? new CallbackFactory(Callback::class);

        if (!isset($config['property_path'])) {
            $config['property_path'] = 'state';
        }

        $this->config = $config;

        // Test if the given object has the given state property path
        try {
            $this->getState();
        } catch (NoSuchPropertyException $e) {
            throw new SMException(sprintf(
               'Cannot access to configured property path "%s" on object %s with graph "%s"',
                $config['property_path'],
                get_class($object),
                $config['graph']
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function can($transition): bool
    {
        if (!isset($this->config['transitions'][$transition])) {
            throw new SMException(sprintf(
                'Transition "%s" does not exist on object "%s" with graph "%s"',
                $transition,
                get_class($this->object),
                $this->config['graph']
            ));
        }

        if (!in_array($this->getState(), $this->config['transitions'][$transition]['from'])) {
            return false;
        }

        $can = true;
        $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);
        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::TEST_TRANSITION);

            $can = !$event->isRejected();
        }

        return $can && $this->callCallbacks($event, 'guard');
    }

    /**
     * {@inheritDoc}
     */
    public function canAction($action): bool
    {
        $state = $this->getState();
        if (isset($this->config['states'][$state]['actions']))
        {
            if (in_array($action, $this->config['states'][$state]['actions'])) {
                if (array_key_exists('conditions',$this->config['states'][$state]) && array_key_exists($action, $this->config['states'][$state]['conditions'])) {
                    $class = ($this->config['states'][$state]['conditions'][$action][0] == 'object') ? $this->getObject() : $this->config['states'][$state]['conditions'][$action][0];
                    $method = $this->config['states'][$state]['conditions'][$action][1];
                    $condition = $this->config['states'][$state]['conditions'][$action][2];
                    return call_user_func([$class, $method]) === $condition;
                }
                return true;
            }

        }
        return false;
    }

    /**
     * @param $transition
     * @return bool
     * @throws SMException
     */
//    public function canSilent($transition): bool
//    {
//        return $this->can($transition, true);
//    }

    /**
     * {@inheritDoc}
     */
    public function apply($transition, bool $soft = false): bool
    {
        if (!$this->can($transition)) {
            if ($soft) {
                return false;
            }

            throw new SMException(sprintf(
                'Transition "%s" cannot be applied on state "%s" of object "%s" with graph "%s"',
                $transition,
                $this->getState(),
                get_class($this->object),
                $this->config['graph']
            ),4);
        }

        $event = new TransitionEvent($transition, $this->getState(), $this->config['transitions'][$transition], $this);

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::PRE_TRANSITION);

            if ($event->isRejected()) {
                return false;
            }
        }

        $locked = $this->callCallbacks($event, 'lock');
        if (!$locked) {
            throw new SMException(sprintf(
                'Can not lock object "%s" for transition "%s" with graph "%s"',
                get_class($this->object),
                $transition,
                $this->config['graph']
            ), 1);
        }

        $this->callCallbacks($event, 'before');

        $this->setState($this->config['transitions'][$transition]['to']);

        $this->callCallbacks($event, 'after');

        $unlocked = $this->callCallbacks($event, 'unlock');
        if (!$unlocked) {
            throw new SMException(sprintf(
                'Can not unlock object "%s" for transition "%s" with graph "%s"',
                get_class($this->object),
                $transition,
                $this->config['graph']
            ), 2);
        }

        if (null !== $this->dispatcher) {
            $this->dispatcher->dispatch($event, SMEvents::POST_TRANSITION);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function applyAction($action, bool $soft = false)
    {
        if (!$this->canAction($action)) {
            if ($soft) {
                return false;
            }

            throw new SMException(sprintf(
                'Action "%s" cannot be applied on state "%s" of object "%s" with graph "%s"',
                $action,
                $this->getState(),
                get_class($this->object),
                $this->config['graph']
            ), 3);
        }

        $class = null;
        $method = null;
        $args = null;
        foreach ($this->config['callbacks']['action'] as $actions) {
            if ($actions['action'] == $action) {
                if (!isset($actions['on']) || in_array($this->getState(), $actions['on'])) {
                    $class = ($actions['do'][0] == 'object') ? $this->getObject() : $actions['do'][0];
                    $method = $actions['do'][1];
                    $args = isset($actions['args']) ? $actions['args'] : [];
                    break;
                }
            }
        }

        if ($class === null || $method === null)
            throw new SMException(sprintf(
                'Callback not found for action "%s" on state "%s" of object "%s" with graph "%s"',
                $action,
                $this->getState(),
                get_class($this->object),
                $this->config['graph']
            ));


        $result = call_user_func([$class,$method], ...$args) ;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getState()
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return $accessor->getValue($this->object, $this->config['property_path']);
    }

    /**
     * {@inheritDoc}
     */
    public function getObject(): object
    {
        return $this->object;
    }

    /**
     * {@inheritDoc}
     */
    public function getGraph(): string
    {
        return $this->config['graph'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPossibleTransitions(): array
    {
        return array_filter(
            array_keys($this->config['transitions']),
            array($this, 'can')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPossibleActions(): array
    {
        return array_filter(
            array_values($this->config['states'][$this->getState()]['actions']),
            array($this, 'canAction')
        );
    }

    /**
     * Set a new state to the underlying object
     *
     * @param string $state
     *
     * @throws SMException
     */
    protected function setState($state): void
    {
        //DAV if (!in_array($state, $this->config['states'])) {
        if (!array_key_exists($state, $this->config['states'])) {
            throw new SMException(sprintf(
                'Cannot set the state to "%s" to object "%s" with graph %s because it is not pre-defined.',
                $state,
                get_class($this->object),
                $this->config['graph']
            ));
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->object, $this->config['property_path'], $state);
    }

    /**
     * Builds and calls the defined callbacks
     *
     * @param TransitionEvent $event
     * @param string $position
     * @return bool
     */
    protected function callCallbacks(TransitionEvent $event, string $position): bool
    {
        if (!isset($this->config['callbacks'][$position])) {
            return true;
        }

        $result = true;
        foreach ($this->config['callbacks'][$position] as &$callback) {
            if (!$callback instanceof CallbackInterface) {
                $callback = $this->callbackFactory->get($callback);
            }

            $result = call_user_func($callback, $event) && $result;
        }
        return $result;
    }

    /** Get Transition Properties
     * @param $transition
     * @return |null
     */
    public function getTransitionProperties($transition) {
        if (isset($this->config['transitions'][$transition]['properties']))
            return  $this->config['transitions'][$transition]['properties'];
        return null;
    }

    /** Get State properties
     * @param $state
     * @return |null
     */
    public function getStateProperties() {
        $state = $this->getState();
        if (isset($this->config['states'][$state]['properties']))
            return  $this->config['states'][$state]['properties'];
        return null;
    }

    /** Check for transition property
     * @param $transition
     * @param $propertie
     * @return bool
     */
    public function hasTransitionProperties($transition, $propertie): bool {
        if (isset($this->config['transitions'][$transition]['properties']) &&
            (in_array($propertie, $this->config['transitions'][$transition]['properties']) ||
             array_key_exists($propertie, $this->config['transitions'][$transition]['properties'])))
            return true;
        else
            return false;
    }

    /** Check for state property
     * @param $state
     * @param $propertie
     * @return bool
     */
    public function hasStateProperties($propertie): bool {
        $state = $this->getState();
        if (isset($this->config['states'][$state]['properties']) &&
            (in_array($propertie, $this->config['states'][$state]['properties']) ||
                array_key_exists($propertie, $this->config['states'][$state]['properties'])))
            return true;
        else
            return false;
    }

}
