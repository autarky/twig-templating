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

use Symfony\Component\EventDispatcher\Event;

class TemplateEvent extends Event
{
	/**
	 * @var Template
	 */
	protected $template;

	/**
	 * @var TemplateContext
	 */
	protected $context;

	/**
	 * @param Template $template
	 * @param TemplateContext $context
	 */
	public function __construct(Template $template, TemplateContext $context = null)
	{
		$this->template = $template;
		$this->context = $context ?: $template->getContext();
	}

	/**
	 * Get the template instance.
	 *
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Get the template's context instance.
	 *
	 * @return \Autarky\TwigTemplating\TemplateContext
	 */
	public function getContext()
	{
		return $this->context;
	}
}
