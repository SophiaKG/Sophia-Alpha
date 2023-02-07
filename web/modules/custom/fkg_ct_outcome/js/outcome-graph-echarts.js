/**
 * @file
 */

(function ($, window, Drupal) {
  Drupal.behaviors.fkgGraphOutcomeProgram = {
    attach: function (context, drupalSettings) {
      $('.fkg-graph-echarts').each(function (){
        var outcomeId = $(this).data('outcome-id');
        // @see PHP function _fkg_ct_outcome_get_contributing_programs_graph().
        var fkgGraphData = drupalSettings['fkg-graph'][outcomeId];

        var graph = echarts.init(this, null, {
          renderer: 'svg',
          width: 900,
          height: 600,
        });

        graphOption = {
          animation: false,
          title: {
            text: fkgGraphData.title,
            top: 0,
            left: 'center',
          },
          legend: [{
            selectedMode: 'multiple',
            orient: 'vertical',
            left: 0,
            top: '8%',
            width: 200,
            data: fkgGraphData.categories.map(
              function (item) {return item.name;}
            ),
            textStyle: {
              width: 200,
              overflow: 'break',
            },
          }],
          series: [{
            name: fkgGraphData.title,
            type: 'graph',
            layout: 'force',
            data: fkgGraphData.nodes,
            links: fkgGraphData.links,
            categories: fkgGraphData.categories,
            label: {
              show: false,
              fontSize: 15
            },
            labellayout: {
              hideOverlap: true
            },
            force: {
              repulsion: 500,
              layoutAnimation: false
            },
          }],
        };

        graph.setOption(graphOption);
      });
    }
  };
})(jQuery, window, Drupal);

