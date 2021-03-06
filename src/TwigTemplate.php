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

use Autarky\Events\EventDispatcherAwareInterface;
use Autarky\Events\EventDispatcherAwareTrait;

/**
 * Extension of the Twig_Template class to enable event dispatching as well as
 * working with template context objects.
 */
abstract class TwigTemplate extends \Twig_Template implements EventDispatcherAwareInterface
{
	use EventDispatcherAwareTrait;

	/**
	 * @var \Autarky\TwigTemplating\Template
	 */
	protected $template;

	/**
	 * Set the Autarky Template instance.
	 *
	 * @param \Autarky\TwigTemplating\Template $template
	 */
	public function setTemplate(\Autarky\TwigTemplating\Template $template)
	{
		$this->template = $template;
	}

	/**
	 * {@inheritdoc}
	 */
	public function display(array $context, array $blocks = array())
	{
		$templateContext = clone $this->template->getContext();

		if ($context) {
			$templateContext->replace($context);
		}

		if ($this->eventDispatcher !== null) {
			$this->eventDispatcher->dispatch(
				'template.rendering: '.$this->template->getName(),
				new TemplateEvent($this->template, $templateContext)
			);
		}

		parent::display($templateContext->toArray(), $blocks);
	}
}
