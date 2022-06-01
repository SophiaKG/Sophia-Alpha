(function ($, window, Drupal) {
  Drupal.behaviors.outcomeGraph = {
    attach: function (context, drupalSettings) {
      var graphviz = d3.select('#outcome-graph', context).graphviz({
          useWorker: false,
          engine: 'fdp',
          fade: true,
          overlap: false,
          scale: 0.9
        });

      // Retrieve the dot graph data.
      var graph = drupalSettings['fkg_graph'];

      // Render the graph.
      graphviz.renderDot(graph);
    }
  };
})(jQuery, window, Drupal);
