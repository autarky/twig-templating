<?php
/**
 * This file is part of the Autarky package.
 *
 * (c) Andreas Lutro <anlutro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Autarky\TwigTemplating;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Autarky\Events\EventDispatcherAwareInterface;

/**
 * A wrapper for Twig.
 */
class TemplatingEngine implements EventDispatcherAwareInterface
{
	/**
	 * The Twig environment instance.
	 *
	 * @var TwigEnvironment
	 */
	protected $twig;

	/**
	 * The event dispatcher instance.
	 *
	 * @var EventDispatcherInterface
	 */
	protected $eventDispatcher;

	/**
	 * @param TwigEnvironment $twig
	 */
	public function __construct(TwigEnvironment $twig)
	{
		$this->twig = $twig;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
	{
		$this->eventDispatcher = $eventDispatcher;
		$this->twig->setEventDispatcher($eventDispatcher);
	}

	/**
	 * Render a template.
	 *
	 * @param  string $name
	 * @param  array  $context
	 *
	 * @return string
	 */
	public function render($name, array $context = array())
	{
		return $this->twig->loadTemplate($name)
			->render($context);
	}

	/**
	 * Register an event listener for when a template is being created.
	 *
	 * @param  string          $name
	 * @param  \Closure|string $handler
	 * @param  integer         $priority
	 *
	 * @return void
	 */
	public function creating($name, $handler, $priority = 0)
	{
		$this->addEventListener('creating', $name, $handler, $priority);
	}

	/**
	 * Register an event listener for when a template is being rendered.
	 *
	 * @param  string          $name
	 * @param  \Closure|string $handler
	 * @param  integer         $priority
	 *
	 * @return void
	 */
	public function rendering($name, $handler, $priority = 0)
	{
		$this->addEventListener('rendering', $name, $handler, $priority);
	}

	protected function addEventListener($event, $name, $handler, $priority = 0)
	{
		if ($this->eventDispatcher === null) {
			throw new \RuntimeException('Cannot register templating event listeners without first setting the EventDispatcher on the TemplateManager.');
		}

		$this->eventDispatcher->addListener("template.$event: $name", $handler, $priority);
	}

	/**
	 * Add a global variable.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 *
	 * @return void
	 */
	public function addGlobal($name, $value)
	{
		$this->twig->addGlobal($name, $value);
	}

	/**
	 * Register a template namespace.
	 *
	 * @param  string $namespace
	 * @param  string $location
	 *
	 * @return void
	 */
	public function addNamespace($namespace, $location)
	{
		$loader = $this->twig->getLoader();

		$loader->addPath($location, $namespace);

		foreach ($loader->getPaths() as $path) {
			if (is_dir($dir = $path.'/'.$namespace)) {
				$loader->prependPath($dir, $namespace);
			}
		}
	}
}
