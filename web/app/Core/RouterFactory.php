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

        // Mini variant: short-link redirector on the qr.<appDomain> subdomain.
        // `<code .+>` lets the slug span multiple path segments (slashes allowed);
        // the query string is intentionally not part of the code.
        $router->withDomain("qr.$appDomain")
            ->addRoute('<code .+>', 'Redirect:default');

        $app = $router->withDomain($appDomain);

        // Administration under the admin/ prefix (Admin module). Must precede the
        // public catch-all route below.
        $app->withModule('Admin')
            ->addRoute('admin[/<presenter>[/<action>[/<id>]]]', 'Dashboard:default');

        // Public file server under /soubor/<slug>. `<slug .+>` allows slashes (multi
        // segment paths). Must precede the public catch-all below, which would
        // otherwise swallow it as presenter/action/id.
        $app->addRoute('soubor/<slug .+>', 'File:default');

        // Public IVR endpoint for the ODORIK exchange under /ivr/<code>. `<code .+>` allows
        // slashes; must precede the public catch-all below.
        $app->addRoute('ivr/<code .+>', 'Ivr:default');

        // Public part. Fully-optional segments so default presenter/action collapse
        // cleanly (avoids a trailing-slash canonical redirect under withDomain).
        $app->addRoute('[<presenter>[/<action>[/<id>]]]', 'Home:default');

        return $router;
    }
}
