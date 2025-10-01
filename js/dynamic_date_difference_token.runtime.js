(function (Drupal, drupalSettings) {
  Drupal.behaviors.dynamicDateDifferenceToken = {
    attach: function (context, settings) {
      context.querySelectorAll('span.dynamic-token[data-token-type-id="dynamic_date_difference_token"][data-target-datetime]').forEach(function (el) {
        if (!Drupal.dynamicTokens.once('dynamicDateBound', el)) return;
        var speedMs = Drupal.dynamicTokens.parseSpeed(el, 1000);
        var targetIso = el.getAttribute('data-target-datetime');
        var target = new Date(targetIso);
        function tick() {
          var diff = Math.round((target.getTime() - Date.now()) / 1000);
          el.textContent = (Math.abs(diff) / 31536000).toFixed(8);
        }
        tick();
        var handle = setInterval(tick, speedMs);
        Drupal.dynamicTokens.intervals.set(el, handle);
        document.addEventListener('visibilitychange', function(){
          if (document.hidden) { clearInterval(handle); }
          else { handle = setInterval(tick, speedMs); }
        });
      });
    },
    detach: function (context) {
      context.querySelectorAll('span.dynamic-token[data-token-type-id="dynamic_date_difference_token"]').forEach(function (el) {
        var h = Drupal.dynamicTokens.intervals.get(el);
        if (h) { clearInterval(h); Drupal.dynamicTokens.intervals.delete(el); }
      });
    }
  };
})(Drupal, drupalSettings);
