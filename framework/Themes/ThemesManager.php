<?php
namespace SleepingOwl\Framework\Themes;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use SleepingOwl\Framework\Contracts\Themes\Factory as ThemeFactory;
use SleepingOwl\Framework\Contracts\Themes\Theme as ThemeContract;
use SleepingOwl\Framework\Events\ThemeLoaded;
use SleepingOwl\Framework\Exceptions\Themes\ThemeNotFound;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThemesManager implements ThemeFactory
{

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Список доступных тем
     *
     * @var array
     */
    protected $themes = [];

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    /**
     * @var Dispatcher
     */
    private $events;

    /**
     * @param Application $app
     * @param Dispatcher $events
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        $this->app = $app;
        $this->events = $events;
        $this->resolver = new OptionsResolver();

        $this->configureOptions($this->resolver);
    }

    /**
     * Получение объекта текущей темы
     *
     * @return ThemeContract
     */
    public function theme(): ThemeContract
    {
        $name = $this->getDefaultTheme();

        return $this->themes[$name] = $this->get($name);
    }

    /**
     * @param string $name
     *
     * @return ThemeContract
     */
    protected function get(string $name): ThemeContract
    {
        return isset($this->themes[$name]) ? $this->themes[$name] : $this->resolve($name);
    }

    /**
     * Получение ключа темы по умолчанию
     *
     * @return string
     */
    public function getDefaultTheme(): string
    {
        return $this->app['config']['sleepingowl.theme.default'];
    }

    /**
     * Изменение темы по умолчанию
     *
     * @param  string $name
     *
     * @return void
     */
    public function setDefaultTheme(string $name)
    {
        $this->app['config']['sleepingowl.theme.default'] = $name;
    }

    /**
     * Получаение настроек для темы
     *
     * @param  string $name
     *
     * @return array
     */
    protected function getConfig($name): array
    {
        return (array) $this->app['config']["sleepingowl.theme.themes.{$name}"];
    }

    /**
     * Создание объекта из переданного класса
     *
     * @param string $name
     *
     * @return ThemeContract
     * @throws ThemeNotFound
     */
    protected function resolve(string $name): ThemeContract
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new ThemeNotFound("Theme [{$name}] is not defined.");
        }

        $config = $this->resolver->resolve($config);
        $class = $config['class'];

        if (! class_exists($class)) {
            throw new ThemeNotFound("Theme [{$name}] class not found");
        }

        $theme = $this->app->make($class, ['config' => $config]);

        $this->events->fire(new ThemeLoaded($theme));

        return $theme;
    }

    /**
     * Настройка валидатора для конфига подключаемой темы
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('class');
        $resolver->setAllowedTypes('class', 'string');
    }

    /**
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->theme()->$method(...$parameters);
    }
}