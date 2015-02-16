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

use Twig_Environment;
use Twig_LoaderInterface;

use Autarky\Events\EventDispatcherAwareInterface;
use Autarky\Events\EventDispatcherAwareTrait;

/**
 * Autarky extension of the Twig_Environment class in order to add event
 * dispatching capabilities.
 */
class TwigEnvironment extends Twig_Environment implements EventDispatcherAwareInterface
{
	use EventDispatcherAwareTrait;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(Twig_LoaderInterface $loader = null, $options = array())
	{
		$options['base_template_class'] = 'Autarky\TwigTemplating\TwigTemplate';

		parent::__construct($loader, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadTemplate($path, $index = null)
	{
		$template = new \Autarky\TwigTemplating\Template($path);

		if ($this->eventDispatcher !== null) {
			$this->eventDispatcher->dispatch('template.creating: '.$path,
				new TemplateEvent($template));
		}

		/** @var \Autarky\TwigTemplating\Twig\Template $twigTemplate */
		$twigTemplate = parent::loadTemplate($path, $index);
		$twigTemplate->setTemplate($template);
		$twigTemplate->setEventDispatcher($this->eventDispatcher);

		return $twigTemplate;
	}
}
