<?php
/**
 * Factory for Shibboleth authentication module.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace VuFind\Auth;

use Interop\Container\ContainerInterface;
use VuFind\Auth\Shibboleth\MultiIdPConfigurationLoader;
use VuFind\Auth\Shibboleth\SingleIdPConfigurationLoader;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Factory for Shibboleth authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ShibbolethFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    const SHIBBOLETH_CONFIG_FILE_NAME = "config";

    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $loader = $this->getConfigurationLoader($container);
        return new $requestedName(
            $container->get(\Laminas\Session\SessionManager::class), $loader
        );
    }

    /**
     * Return configuration loader for shibboleth
     *
     * @param ContainerInterface $container Service manager
     *
     * @return configuration loader
     */
    public function getConfigurationLoader(ContainerInterface $container)
    {
        $config = $container->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        $override = $config->Shibboleth->override;
        $loader = null;
        if (!empty($override)) {
            $shibConfig = $container->get('VuFind\Config')->get($override);
            $loader = new MultiIdPConfigurationLoader($config, $shibConfig);
        } else {
            $loader = new SingleIdPConfigurationLoader($config);
        }
        return $loader;
    }
}
