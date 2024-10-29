(function ($, window, ap3Options) {
  "use strict";

  window.ap3c = window.ap3c || {};
  var ap3c = window.ap3c;
  ap3c.cmd = ap3c.cmd || [];
  ap3c.cmdWooCommerce = ap3c.cmdWooCommerce || [];

  var emailInputs = {
    billing: $('input[name="billing_email"]'),
    input: $('input[type="email"]'),
  };

  var ctx = {
    customer: null,
    input: null,
    billing: null,
    trackedEmails: {}
  };

  ap3c.cmdWooCommerce.push(function () {
    if (ap3Options.customer && ap3Options.customer.email) {
      ctx.customer = ap3Options.customer.email;
    }

    $.each(emailInputs, function (key, input) {
      ctx[key] = input.val();
      input.blur(function () {
        ctx[key] = input.val();
        ap3c.trackWooCommerce(window.ap3Event);
      });
    });

    ap3c.initWooCommerce(ctx, ap3Options, window.ap3Event);
  });

  var s, t; s = document.createElement('script'); s.type = 'text/javascript'; s.src = ap3Options.capture_js_url;
  t = document.getElementsByTagName('script')[0]; t.parentNode.insertBefore(s, t);
  s.addEventListener('load', function() {
    var bs, bt; bs = document.createElement('script'); bs.type = 'text/javascript'; bs.src = ap3Options.woocommerce_js_url;
    bt = document.getElementsByTagName('script')[0]; bt.parentNode.insertBefore(bs, bt);
  });
})(jQuery, window, ap3Options);
