(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.renderDotSVG = {
    attach: function (context, settings) {
      var graph = Viz(drupalSettings.entity_relationship_diagram.dataSVG, { format: "png-image-element" });
      document.body.appendChild(graph);
    }
  };
})(jQuery, Drupal);
