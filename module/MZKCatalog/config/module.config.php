<?php
namespace MZKCatalog\Module\Configuration;

$config = array(
    'controllers' => array(
        'invokables' => array(
            'my-research' => 'MZKCatalog\Controller\MyResearchController'
        ) /* invokables */	
    ), /* controllers */
    'service_manager' => array(
        'factories' => array( 
            'VuFind\ILSHoldLogic' => function ($sm) {
                return new \MZKCatalog\ILS\Logic\Holds(
                    $sm->get('VuFind\AuthManager'), $sm->get('VuFind\ILSConnection'),
                    $sm->get('VuFind\HMAC'), $sm->get('VuFind\Config')->get('config')
                );
            },
        ),
    ),
    'vufind' => array(
        'plugin_managers' => array (
            'ils_driver' => array(
            'factories' => array(
                'aleph' => function ($sm) {
                    return new \MZKCatalog\ILS\Driver\Aleph(
                        $sm->getServiceLocator()->get('VuFind\DateConverter'),
                        $sm->getServiceLocator()->get('VuFind\CacheManager')
                    );
                } /* aleph */
            ) /* factories */
            ),
            'recorddriver' => array (
                'factories' => array(
                    'solrmzk' => function ($sm) {
                        $driver = new \MZKCatalog\RecordDriver\SolrMarc(
                            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                            null,
                            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                        );
                        $driver->attachILS(
                            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                        );
                        return $driver;
                    },
                ) /* factories */
            ), /* recorddriver */
        ), /* plugin_managers */
    ), /* vufind */
);

$staticRoutes = array('MyResearch/CheckedOutHistory');

// Build static routes
foreach ($staticRoutes as $route) {
    list($controller, $action) = explode('/', $route);
    $routeName = str_replace('/', '-', strtolower($route));
    $config['router']['routes'][$routeName] = array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/' . $route,
            'defaults' => array(
            'controller' => $controller,
                'action'     => $action,
            )
        )
    );
}

return $config;
