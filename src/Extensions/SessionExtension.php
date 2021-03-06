<?php
/**
 * This file is part of the Autarky package.
 *
 * (c) Andreas Lutro <anlutro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Autarky\TwigTemplating\Extensions;

use Twig_Extension;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Extension that adds session-related functionality.
 *
 * Adds the 'flash' global variable containing flash messages.
 */
class SessionExtension extends Twig_Extension
{
	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * Constructor.
	 *
	 * @param Session $session
	 */
	public function __construct(Session $session)
	{
		$this->session = $session;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGlobals()
	{
		return [
			'flash' => $this->session->getFlashBag()->peek('_messages', []),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'session';
	}
}
