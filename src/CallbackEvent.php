<?php

namespace omnilight\scheduling;

use Yii;
use yii\base\Application;
use InvalidArgumentException;
use yii\mutex\Mutex;

/**
 * Class CallbackEvent
 *
 * @property-read string $summaryForDisplay
 */
class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * @var string
     */
    protected string $callback;
    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected array $parameters;

    /**
     * Create a new event instance.
     *
     * @param \yii\mutex\Mutex $mutex
     * @param string           $callback
     * @param array            $parameters
     * @param array            $config
     */
    public function __construct(Mutex $mutex, string $callback, array $parameters = [], array $config = []) {
        $this->callback = $callback;
        $this->parameters = $parameters;
        $this->_mutex = $mutex;

        if (!empty($config)) {
            Yii::configure($this, $config);
        }

        if (!is_string($this->callback) && !is_callable($this->callback)) {
            throw new InvalidArgumentException("Invalid scheduled callback event. Must be string or callable.");
        }
    }

    /**
     * Run the given event.
     *
     * @param Application $app
     *
     * @return mixed
     */
    public function run(Application $app): mixed {
        $this->trigger(self::EVENT_BEFORE_RUN);
        $response = call_user_func_array($this->callback, array_merge($this->parameters, [$app]));
        $this->callAfterCallbacks($app);
        $this->trigger(self::EVENT_AFTER_RUN);

        return $response;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @return $this
     */
    public function withoutOverlapping(): self {
        if (empty($this->_description)) {
            throw new InvalidArgumentException(
                "A scheduled event name is required to prevent overlapping. Use the 'description' method before 'withoutOverlapping'."
            );
        }

        return parent::withoutOverlapping();
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * @return string
     */
    protected function mutexName(): string {
        return 'framework/schedule-' . sha1($this->_description);
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay(): string {
        if (is_string($this->_description)) {
            return $this->_description;
        }

        return is_string($this->callback) ? $this->callback : 'Closure';
    }

}
