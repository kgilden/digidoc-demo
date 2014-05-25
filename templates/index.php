<!DOCTYPE html>
<html>
<head>
    <title>DigiDoc teenuse demo - uute konteinerite loomine</title>
    <link rel="stylesheet" href="/css/digidoc.css"></style>
</head>
<body>

    <div class="intro">
        <h1>DigiDoc teenuse demo - uute konteinerite loomine</h1>

    </div>

    <div class="flashes">
        <?php foreach ($session->getFlashBag()->all() as $type => $flashes): ?>
            <?php foreach ($flashes as $flash): ?>
                <div class="flash-<?php echo $type; ?>"><?php echo $app->escape($flash); ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <?php if($session->has('container')): ?>
    <hr />

    <div class="digidoc-container">
        <h2>Salvestatud konteiner (digidoc teenuse sessiooni id: <?php echo $container->getSession()->getId() ?>)</h2>

        <h3>Failid</h3>
        <table class="digidoc-container-files">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nimi</th>
                    <th>Sisutüüp</th>
                    <th>Suurus (B)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($container->getFiles() as $file): ?>
                <tr>
                    <td><pre><?php echo $app->escape($file->getId()) ?></pre></td>
                    <td><pre><?php echo $app->escape($file->getName()) ?></pre></td>
                    <td><pre><?php echo $app->escape($file->getMimeType()) ?></pre></td>
                    <td><pre><?php echo $app->escape($file->getSize()) ?></pre></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Allkirjad</h3>
        <table class="digidoc-container-signatures js-signatures">
            <thead>
                <tr>
                    <th class="digidoc-signature-id">Id</th>
                    <th>Serdi id</th>
                    <th class="digidoc-signature-cert-signature">Serdi signatuur</th>
                    <th>Ülesanne</th>
                    <th>Lahendus</th>
                    <th>Kinnitatud?</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($container->getSignatures() as $signature): ?>
                <tr class="js-signature">
                    <td class="monospaced digidoc-signature-id">
                        <textarea><?php echo $app->escape($signature->getId()) ?></textarea>
                    </td>
                    <td class="monospaced js-signature-cert-id">
                        <textarea><?php echo $app->escape($signature->getCertId()) ?></textarea>
                    </td>
                    <td class="monospaced digidoc-signature-cert-signature">
                        <textarea><?php echo $app->escape($signature->getCertSignature()) ?></textarea>
                    </td>
                    <td class="monospaced js-signature-challenge">
                        <textarea><?php echo $app->escape($signature->getChallenge()) ?></textarea>
                    </td>
                    <td>
                        <form method="post" action="<?php echo $app['url_generator']->generate('seal_signature') ?>">
                            <textarea name="signature_solution" class="monospaced js-signature-solution"><?php
                                echo $app->escape($signature->getSolution() ?: '')
                            ?></textarea>
                            <input name="signature_id" type="hidden" value="<?php echo $app->escape($signature->getId()) ?>" />
                            <button class="js-btn-solve" type="button" <?php if ($signature->getSolution()) echo 'disabled' ?>>Lahenda</button>
                            <button class="js-btn-finalize" <?php if (!$signature->getSolution() || $signature->isSealed()) echo 'disabled' ?>>Kinnita</button>
                        </form>
                    </td>
                    <td>
                        <pre><?php echo $signature->isSealed() ? 'jah' : 'ei' ?></pre>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form class="digidoc-clear-container" method="post" action="<?php echo $app['url_generator']->generate('delete') ?>">
        <input type="submit" name="delete" value="Kustuta" />
        <a href="<?php echo $app['url_generator']->generate('download') ?>">Tiri alla</a>
    </form>

    <form class="digidoc-add-file" method="post" action="<?php echo $app['url_generator']->generate('add_file') ?>" enctype="multipart/form-data">
        <h3>Lisa fail</h3>
        <input type="file" name="file" />
        <br />
        <input type="submit" name="submit" value="Lisa" />
    </form>

    <form class="digidoc-add-signature js-cert" method="post" action="<?php echo $app['url_generator']->generate('add_signature') ?>">
        <h3>Lisa allkiri</h3>

        <div>
            <input id="add_cert_id" class="js-cert-id" type="text" name="cert[id]" value="" />
            <label for="add_cert_id">Serdi id</label>
        </div>

        <div>
            <textarea id="add_cert_signature" class="js-cert-signature" type="text" name="cert[signature]"></textarea>
            <label for="add_cert_signature">Serdi signatuur</label>
        </div>

        <button class="js-add-signature" type="button">Lisa kaardilt allkiri</button>
        <button>Allkirjasta</button>
    </form>
    <?php endif; ?>

    <hr />

    <form class="form" method="post" action="<?php echo $app['url_generator']->generate('create') ?>" enctype="multipart/form-data">

        <h2>Uus konteiner</h1>

        <fieldset>
            <legend>Failid</legend>

            <ul>
                <li><input id="files" name="file" type="file" /></li>
            </ul>
        </fieldset>

        <fieldset class="js-cert">
            <legend>Allkirjad</legend>

            <div>
                <input id="new_cert_id" class="js-cert-id" type="text" name="cert[id]" value="" />
                <label for="new_cert_id">Serdi id</label>
            </div>

            <div>
                <textarea id="new_cert_signature" class="js-cert-signature" type="text" name="cert[signature]"></textarea>
                <label for="new_cert_signature">Serdi signatuur</label>
            </div>

            <button class="js-add-signature" type="button" >Lisa kaardilt allkiri</button>
        </fieldset>

        <input type="submit" name="sign" value="Allkirjasta" />

    </form>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="/js/idCard.js"></script>
    <script src="/js/js-digidoc.js"></script>
</body>
</html>
