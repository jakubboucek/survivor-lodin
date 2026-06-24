<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(DomainProvider $domainProvider): RouteList
    {
        $appDomain = $domainProvider->getCurrentDomain();

        $router = new RouteList;

        // Mini variant: QR redirector on the qr.<appDomain> subdomain.
        // Short single-segment code keeps the encoded QR small.
        $router->withDomain("qr.$appDomain")
            ->addRoute('<code>', 'Redirect:default');

        $app = $router->withDomain($appDomain);

        // Administration under the admin/ prefix (Admin module). Must precede the
        // public catch-all route below.
        $app->withModule('Admin')
            ->addRoute('admin[/<presenter>[/<action>[/<id>]]]', 'Dashboard:default');

        // Public part. Fully-optional segments so default presenter/action collapse
        // cleanly (avoids a trailing-slash canonical redirect under withDomain).
        $app->addRoute('[<presenter>[/<action>[/<id>]]]', 'Home:default');

        return $router;
    }
}
