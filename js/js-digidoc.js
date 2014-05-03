
(function (window, document) {
    'use strict';

    var digidoc = {};

    digidoc._plugin = null;

    digidoc.getPlugin = function () {

        if (!this._plugin) {
            $('body').append('<div id="pluginLocation" />');

            loadSigningPlugin('et');

            this._plugin = new window.digidocPluginHandler('et');
        }


        return this._plugin;
    };

    digidoc.getCert = function () {
        var plugin = this.getPlugin(),
            pluginCert = plugin.getCertificate(),
            cert;

        cert = {
            id: pluginCert.id,
            signature: pluginCert.cert || pluginCert.certificateAsHex,
            cnClient: pluginCert.CN,
            cnIssuer: pluginCert.issuerCN,
        };

        return cert;
    };

    digidoc.sign = function (certId, challenge) {
        return this.getPlugin().sign(certId, challenge);
    };

    window.digidoc = digidoc;

    if (!document.getElementsByClassName) {
        document.getElementsByClassName = function(classname) {
            var elArray = [];
            var tmp = document.getElementsByTagName("*");
            var regex = new RegExp("(^|\\s)" + classname + "(\\s|$)");
            for (var i = 0; i < tmp.length; i++) {

                if (regex.test(tmp[i].className)) {
                    elArray.push(tmp[i]);
                }
            }

            return elArray;
        };
    }

    function init() {
        $('.js-cert').on('click', '.js-add-signature', addSignature);
        $('.js-signatures').on('click', '.js-btn-solve', solveChallenge);

    }

    function addSignature(e) {
        var cert = digidoc.getCert();

        $(e.delegateTarget).find('.js-cert-id').val(cert.id);
        $(e.delegateTarget).find('.js-cert-signature').val(cert.signature);
    }

    function solveChallenge() {
        var $btn = $(this),
            $signature = $btn.closest('.js-signature'),
            certId     = $.trim($signature.find('.js-signature-cert-id').text()),
            challenge  = $.trim($signature.find('.js-signature-challenge').text()),
            solution;

        solution = digidoc.sign(certId, challenge);

        $signature.find('.js-signature-solution').text(solution).val(solution);
        $btn.attr('disabled', true);
        $('.js-btn-finalize').removeAttr('disabled');
    }

    init();

})(window, window.document, jQuery);
