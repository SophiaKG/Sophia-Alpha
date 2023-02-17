/**
 * @file
 */

(function ($, window, Drupal) {
  Drupal.behaviors.fkgGraphOutcomeProgram = {
    attach: function (context, drupalSettings) {
      $('.fkg-graph-echarts').each(function (){
        var outcomeId = $(this).data('outcome-id');
        // Retrieve the data prepared by the PHP backend.
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
            aria: {
              enabled: true,
            },
            name: fkgGraphData.title,
            type: 'graph',
            layout: 'force',
            data: fkgGraphData.nodes,
            links: fkgGraphData.links,
            edgeSymbol: ['none', 'arrow'],
            categories: fkgGraphData.categories,
            label: {
              show: false,
              fontSize: 15,
              color: 'black',
            },
            labellayout: {
              hideOverlap: true
            },
            force: {
              repulsion: 500,
              layoutAnimation: false
            },
            lineStyle: {
              opacity: 1,
              width: 2,
            },
            tooltip: {
              trigger: 'item',
            },
            selectedMode: 'single',
            select: {
              label: {
                show: true,
                position: 'bottom',
              },
              itemStyle: {
                borderColor: 'red',
                borderWidth: 3,
              },
              lineStyle: {
                width: 10,
                opacity: 1,
              },
              symbolSize: 20,
            },
          }],
          tooltip: {
            show: true,
            showContent: true,
            alwaysShowContent: true,
            confine: true,
            position: ['80%', '10%'],
            trigger: 'item',
            triggerOn: 'click',
            enterable: true,
            extraCssText: 'white-space:normal; width:300px;',
          },
        };

        graph.setOption(graphOption);
      });
    }
  };
})(jQuery, window, Drupal);

