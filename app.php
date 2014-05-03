<?php

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use KG\DigiDoc\Signature;

$app = require(__DIR__.'/bootstrap.php');
$app['debug'] = true;

// Start the server with:
// php -S localhost:8080
// stunnel -d 4443 -f -r 8080 -p ~/projects/stack-oscp/tests/certs/pems/server.pem -P /tmp/stunnel.pid

/**
 * Starts the session before each request.
 */
$app->before(function (Request $request) {
    $request->getSession()->start();
});

/**
 * The index page. This basically renders the index template.
 */
$app->get('/index.html', function(Request $request) use ($app) {

    return renderTemplate(__DIR__.'/templates/index.php', array(
        'session'   => $request->getSession(),
        'container' => $request->getSession()->get('container'),
        'request'   => $request,
        'app'       => $app,
    ));

})->bind('home');

/**
 * Creates a new DigiDoc container and fills it with the posted file and
 * signature. The signature must still be finalized. This has to be done
 * as a separate request.
 *
 * The POST request parameters must have the following structure:
 *
 *     cert[id]        - certificate id
 *     cert[signature] - certificate signature
 *
 * Additionally the uploaded file itself must be named 'file' (e.g. having
 * '<input type="file" name="file" /> as the input element).
 */
$app->post('/container/create', function (Request $request) use ($app) {
    $session = $request->getSession();

    $path = getTempDir($request);

    $api = $app['digidoc.api'];

    // Creates a new empty DigiDoc container.
    $container = $api->create();

    // Only adds a file to the container, if it was sent with the request.
    if ($file = $request->files->get('file')) {
        // Moves the posted file to a temporary location (unique per HTTP session).
        $file = $file->move($path, $file->getClientOriginalName());

        // Adds the uploaded file to the container. NB! This is not synced with
        // the DigiDoc service yet, see below.
        $container->addFile($file->getPathname());

        $session->getFlashBag()->add('success', 'Konteinerile lisati fail '.$file->getFilename());
    }

    $cert = $request->request->get('cert', array());

    // Only adds a signature if both the certificate id & signature were filled.
    if (isset($cert['id'], $cert['signature']) && $cert['id'] && $cert['signature']) {
        $container->addSignature(new Signature($cert['id'], $cert['signature']));

        $session->getFlashBag()->add('success', 'Konteinerile lisati allkiri serdi id-ga '.$certId);
    }

    // Finally updates the DigiDoc service to reflect the container on the 3rd
    // party server. This is the point where the file & signature are added to
    // the container from the point of view of the DigiDoc service.
    $api->update($container);

    // Writes the current state of the container to the temporary location. This
    // way we have an up-to-date local copy of the container on our hard drive.
    $api->write($container, $path.'/container.bdoc');

    // Stores the container in the session by serializing it. This way we can
    // continue using it on subsequent requests.
    $session->set('container', $container);

    // Redirects the client back to the home page. This is so that page refreshes
    // wouldn't post the same file & signature again.
    return redirectTo($app, 'home');
})->bind('create');

/**
 * Seals a specific signature of the container. The documentation calls this
 * process as signature finalizing.
 *
 * The POST request parameters must have the following structure:
 *
 *     signature_id       - signature id (e.g. 'S01')
 *     signature_solution - the solution to the posed challenge
 *
 * No validation is being done here to keep it short and to the point.
 */
$app->post('/container/seal-signature', function (Request $request) use ($app) {
    $container = $request->getSession()->get('container');

    // Gets the signature from the cotnainer with its id and injects the
    // given solution.
    $signature = $container->getSignature($request->request->get('signature_id'));
    $signature->setSolution($request->request->get('signature_solution'));

    // Syncs up to the DigiDoc service. The second parameter here indicates that
    // we also want to "merge" the container with the API. This is necessary
    // because the container was deserialized from the session and the API is
    // currently not tracking any changes so it would ask the DigiDoc service
    // to create all its files and signatures again.
    $app['digidoc.api']->update($container, true);

    $session->getFlashBag()->add('success', 'Allkirja kinnitamine õnnestus!');

    return redirectTo($app, 'home');
})->bind('seal_signature');

/**
 * Adds a new file to the current container.
 */
$app->post('/container/add-file', function (Request $request) use ($app) {
    $session = $request->getSession();

    $file = $request->files->get('file');
    $file = $file->move(getTempDir($request), $file->getClientOriginalName());

    $api = $app['digidoc.api'];
    $api->merge($container = $session->get('container'));

    $container->addFile($file->getPathname());

    $api->update($container);

    $session->getFlashBag()->add('success', 'Konteinerile lisati fail '.$file->getFilename());

    return redirectTo($app, 'home');
})->bind('add_file');

$app->post('/container/add-signature', function (Request $request) use ($app) {
    $session = $request->getSession();

    $cert = $request->request->get('cert', array());

    $api = $app['digidoc.api'];
    $api->merge($container = $session->get('container'));

    $container->addSignature(new Signature($cert['id'], $cert['signature']));

    $api->update($container);

    $session->getFlashBag()->add('success', 'Konteinerile lisati allkiri serdi id-ga '.$certId);

    return redirectTo($app, 'home');
})->bind('add_signature');

$app->post('/container/delete', function (Request $request) use ($app) {
    unlink(getTempDir($request));

    $container = $request->getSession()->remove('container');

    $app['digidoc.api']->close($container);

    die('foo');

    $request->getSession()->getFlashBag()->add('success', 'Konteiner kustutati edukalt.');

    return redirectTo($app, 'home');
})->bind('delete');

$app->get('/container.bdoc', function (Request $request) use ($app) {
    $container = $request->getSession()->get('container');

    $api = $app['digidoc.api'];
    $api->merge($container);
    $api->write($container, $path = getTempDir($request).'/container.bdoc');

    return new BinaryFileResponse($path);
})->bind('download');

$app->run();

function getTempDir(Request $request)
{
    return sys_get_temp_dir().'/digidoc/'.$request->getSession()->getId();
}

function redirectTo($app, $route, $routeParams = array())
{
    return new RedirectResponse($app['url_generator']->generate($route, $routeParams));
}

function renderTemplate($path, $arguments)
{
    ob_start();

    extract($arguments, EXTR_SKIP);
    include $path;

    return ob_get_clean();
}
