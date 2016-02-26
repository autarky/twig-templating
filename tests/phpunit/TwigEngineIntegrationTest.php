<?php

use Mockery as m;
use Autarky\Tests\TestCase;
use Autarky\TwigTemplating\TwigEngine;
use Autarky\TwigTemplating\Template;
use Symfony\Component\HttpFoundation\Request;

class TwigEngineIntegrationTest extends TestCase
{
	protected function makeApplication($providers = array(), $env = 'testing')
	{
		$providers[] = 'Autarky\Events\EventDispatcherProvider';
		$providers[] = 'Autarky\TwigTemplating\TwigTemplatingProvider';
		$app = parent::makeApplication((array) $providers, $env);
		$app->getConfig()->set('path.templates', TESTS_RSC_DIR.'/templates');
		$app->getConfig()->set('path.templates-cache', TESTS_RSC_DIR.'/template-cache');
		$app->getConfig()->set('session.driver', 'null');
		$app->getConfig()->set('session.storage', 'mock_array');
		$app->getConfig()->set('app.debug', true);
		return $app;
	}

	protected function makeEngine(array $providers = array())
	{
		$this->app = $this->makeApplication($providers);
		$this->app->boot();
		return $this->app->resolve('Autarky\TwigTemplating\TemplatingEngine');
	}

	/** @test */
	public function extendLayoutWorks()
	{
		$eng = $this->makeEngine();
		$result = $eng->render('template.twig');
		$this->assertEquals('OK', $result);
	}

	/** @test */
	public function urlGenerationViaUrlFunctionWorks()
	{
		$eng = $this->makeEngine(['Autarky\Routing\RoutingProvider']);
		$this->app->getRequestStack()->push(Request::create('/'));
		$this->app->getRouter()
			->addRoute('GET', '/test/route/{param}', function() {}, 'test.route');
		$result = $eng->render('urlgeneration.twig');
		$this->assertEquals('//localhost/test/route/param1', $result);
	}

	/** @test */
	public function partialFunctionWorks()
	{
		$eng = $this->makeEngine();
		$mock = m::mock(['bar' => 'baz']);
		$this->app->getContainer()->instance('foo', $mock);
		$result = $eng->render('partial.twig');
		$this->assertEquals('baz', $result);
	}

	/** @test */
	public function assetUrlGenerationWorksViaAssetFunction()
	{
		$eng = $this->makeEngine(['Autarky\Routing\RoutingProvider']);
		$this->app->getRequestStack()->push(Request::create('/index.php/foo/bar'));
		$result = $eng->render('asseturl.twig');
		$this->assertEquals('//localhost/asset/test.css.js', $result);
	}

	/** @test */
	public function sessionMessagesAreAvailableAsGlobals()
	{
		$sessionProviderClass = class_exists('Autarky\Http\SessionProvider') ?
			'Autarky\Http\SessionProvider' : 'Autarky\Session\SessionProvider';
		$eng = $this->makeEngine([$sessionProviderClass]);
		$session = $this->app->resolve('Symfony\Component\HttpFoundation\Session\Session');
		$data = ['new' => ['_messages' => ['foo', 'bar']]];
		$session->getFlashBag()->initialize($data);
		$result = $eng->render('sessionmsg.twig');
		$this->assertEquals("foo\nbar\n", $result);
	}

	/** @test */
	public function namespacedTemplatesCanBeRendered()
	{
		$eng = $this->makeEngine();
		$eng->addNamespace('namespace', TESTS_RSC_DIR.'/templates/vendor/namespace');
		$result = $eng->render('@namespace/template1.twig');
		$this->assertEquals('OK', $result);
	}

	/** @test */
	public function namespacedTemplatesCanBeOverridden()
	{
		$eng = $this->makeEngine();
		$eng->addNamespace('namespace', TESTS_RSC_DIR.'/templates/vendor/namespace');
		$result = $eng->render('@namespace/template2.twig');
		$this->assertEquals('Overridden', $result);
	}

	/** @test */
	public function eventsAreFiredWhenTemplatesAreCreatedAndRendered()
	{
		$eng = $this->makeEngine();
		$eng->setEventDispatcher($dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher);
		$events = [];
		// can't find a better way to check if symfony/event-dispatcher is 2.x or 3.x
		$symfonyEvents2 = method_exists(new \Symfony\Component\EventDispatcher\Event, 'getName');
		if ($symfonyEvents2) {
			$callback = function($event) use(&$events) { $events[] = $event->getName(); };
		} else {
			$callback = function($event, $name) use(&$events) { $events[] = $name; };
		}
		$eng->creating('template.twig', $callback);
		$eng->rendering('template.twig', $callback);
		$eng->creating('layout.twig', $callback);
		$eng->rendering('layout.twig', $callback);
		$expected = [
			'template.creating: template.twig',
			'template.creating: layout.twig',
			'template.rendering: template.twig',
			'template.rendering: layout.twig',
		];
		$eng->render('template.twig');
		$this->assertEquals($expected, $events);
	}
}
