<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 25.01.15 - 22:57
 */

namespace Application\Service\Factory;

use Application\SharedKernel\ConfigLocation;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ConfigLocationFactory
 *
 * @package Application\Service\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class ConfigLocationFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');

        return ConfigLocation::fromPath($config['system_config_dir']);
    }
}
 