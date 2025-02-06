<?php

namespace omnilight\scheduling;

use Yii;
use yii\mutex\Mutex;
use yii\base\Component;
use yii\base\Application;
use yii\mutex\FileMutex;


/**
 * Class Schedule
 *
 * @property-read \omnilight\scheduling\Event[] $events
 */
class Schedule extends Component
{
    /**
     * All the events on the schedule.
     *
     * @var Event[]
     */
    protected array $_events = [];

    /**
     * The mutex implementation.
     *
     * @var \yii\mutex\Mutex|\yii\mutex\FileMutex|null
     */
    protected Mutex|null|FileMutex $_mutex;

    /**
     * @var string The name of cli script
     */
    public string $cliScriptName = 'yii';

    /**
     * Schedule constructor.
     *
     * @param array $config
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(array $config = []) {
        $this->_mutex = Yii::$app->has('mutex') ? Yii::$app->get('mutex') : (new FileMutex());

        parent::__construct($config);
    }

    /**
     * Add a new callback event to the schedule.
     *
     * @param callable|string $callback
     * @param array           $parameters
     *
     * @return \omnilight\scheduling\Event|\omnilight\scheduling\CallbackEvent
     */
    public function call(callable|string $callback, array $parameters = []): Event|CallbackEvent {
        $this->_events[] = $event = new CallbackEvent($this->_mutex, $callback, $parameters);

        return $event;
    }

    /**
     * Add a new cli command event to the schedule.
     *
     * @param string $command
     *
     * @return Event
     */
    public function command(string $command): Event {
        return $this->exec(PHP_BINARY . ' ' . $this->cliScriptName . ' ' . $command);
    }

    /**
     * Add a new command event to the schedule.
     *
     * @param string $command
     *
     * @return Event
     */
    public function exec(string $command): Event {
        $this->_events[] = $event = new Event($this->_mutex, $command);

        return $event;
    }

    public function getEvents(): array {
        return $this->_events;
    }

    /**
     * Get all the events on the schedule that are due.
     *
     * @param \yii\base\Application $app
     *
     * @return Event[]
     */
    public function dueEvents(Application $app): array {
        return array_filter($this->_events, static function (Event $event) use ($app) {
            return $event->isDue($app);
        });
    }
}
