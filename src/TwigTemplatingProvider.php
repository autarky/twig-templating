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

use Autarky\Container\ContainerInterface;
use Autarky\Provider;

/**
 * Provides the Twig templating engine.
 */
class TwigTemplatingProvider extends Provider
{
	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		$dic = $this->app->getContainer();

		$dic->share('Autarky\TwigTemplating\TemplatingEngine');

		$dic->define('Twig_LoaderInterface', [$this, 'makeTwigLoader']);

		$dic->define('Autarky\TwigTemplating\TwigEnvironment', [$this, 'makeTwigEnvironment']);
		$dic->share('Autarky\TwigTemplating\TwigEnvironment');
		$dic->alias('Autarky\TwigTemplating\TwigEnvironment', 'Twig_Environment');
	}

	/**
	 * Make the twig template loader.
	 *
	 * @return \Autarky\TwigTemplating\TwigFileLoader
	 */
	public function makeTwigLoader()
	{
		return new TwigFileLoader($this->app->getConfig()->get('path.templates'));
	}

	/**
	 * Make the twig environment.
	 *
	 * @return \Autarky\TwigTemplating\TwigEnvironment
	 */
	public function makeTwigEnvironment(ContainerInterface $dic)
	{
		$config = $this->app->getConfig();
		$options = ['debug' => $config->get('app.debug')];

		if ($config->has('path.templates_cache')) {
			$options['cache'] = $config->get('path.templates_cache');
		} else if ($config->has('path.storage')) {
			$options['cache'] = $config->get('path.storage').'/twig';
		}

		$env = new TwigEnvironment($dic->resolve('Twig_LoaderInterface'), $options);

		// merge core framework extensions with user extensions
		$extensions = array_merge([
			'Autarky\TwigTemplating\Extensions\PartialExtension',
			'Autarky\TwigTemplating\Extensions\UrlGenerationExtension' =>
				['Autarky\Routing\UrlGenerator'],
			'Autarky\TwigTemplating\Extensions\SessionExtension' =>
				['Symfony\Component\HttpFoundation\Session\Session'],
		], $this->app->getConfig()->get('twig.extensions', []));

		// iterate through the array of extensions. if the array key is an
		// integer, there are no dependencies defined for that extension and we
		// can simply add it. if the array key is a string, the key is the class
		// name of the extension and the value is an array of class dependencies
		// that must be bound to the service container in order for the
		// extension to be loaded.
		foreach ($extensions as $extension => $dependencies) {
			if (is_int($extension)) {
				$env->addExtension($dic->resolve($dependencies));
			} else {
				foreach ((array) $dependencies as $dependency) {
					if (!$dic->isBound($dependency)) {
						// break out of this inner foreach loop and continue to
						// the next iteration of the outer foreach loop,
						// effectively preventing the extension from loading
						continue 2;
					}
				}

				// if any of the dependencies are not met in the above loop,
				// this line of code will not be executed
				$env->addExtension($dic->resolve($extension));
			}
		}

		return $env;
	}
}
