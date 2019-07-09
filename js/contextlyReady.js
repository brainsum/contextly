(function($) {
  Drupal.behaviors.contextlyReady = {
    attach: function(context, settings) {
      if (typeof Contextly === 'object') {
        Contextly.ready('widgets');
      }
    }
  }
})(jQuery);
