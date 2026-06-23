<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
        use Nette\StaticClass;

        /**
         * @param string $appDomain Base domain (config parameter %appDomain%); the two app
         *                          variants are distinguished by subdomain of this domain.
         */
        public static function createRouter(string $appDomain): RouteList
        {
                $router = new RouteList;

                // Mini variant: QR redirector on the qr.<appDomain> subdomain.
                // Short single-segment code keeps the encoded QR small.
                $router->withDomain("qr.$appDomain")
                        ->addRoute('<code>', 'Redirect:default');

                // Full application (public part + admin) on the bare <appDomain>.
                $router->withDomain($appDomain)
                        ->addRoute('<presenter>/<action>[/<id>]', 'Home:default');

                return $router;
        }
}
