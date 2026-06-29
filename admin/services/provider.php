<?php
/**
 * @package     Com_ContentApiGrabber
 * @subpackage  Services
 * @copyright   (C) 2026 Nick Psaltakis
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Nickpsal\Component\ContentApiGrabber\Administrator\Extension\ContentApiGrabberComponent;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Nickpsal\\Component\\ContentApiGrabber'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Nickpsal\\Component\\ContentApiGrabber'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new ContentApiGrabberComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
