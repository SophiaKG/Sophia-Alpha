<?php

namespace Drupal\neptune_sync\Graph;


use Drupal\neptune_sync\querier\QueryManager;

class GraphGenerator
{
    const GRAPH_CONVERTER_PATH = '';
    const GRAPH_FILETYPE = 'svg';
    const GRAPH_VVISUALIZER_PATH = '/home/ec2-user/workspace/ontology-visualization/' .
                                    'ontology_viz.py';

    protected $working_dir = 'graphs';

    public function generateGraph($path, $query)
    {
        //create a filename
        $name = bin2hex(mcrypt_create_iv(15, MCRYPT_DEV_URANDOM));

        //run the graph creation query
        $query->setOutputPath('resources/ttl/' . $name . '.ttl');
        $query_mgr = new QueryManager();
        $query_mgr->runCustomQuery($query);

        //arg: ontology_viz.py -o [OUTPUT] [INPUT] -O [ONTOLOGY|OPTIONAL]
        //arg: ontology_viz.py -o [NAME].dot [name].ttl
        shell_exec('python ' . self::GRAPH_VVISUALIZER_PATH . '-o resources/dot/'
                    . $name . '.dot ' . 'resources/dot/'. $name . '.ttl');

        //arg: dot -tsvg -o [OUTPUT] [INPUT]
        //arg: dot -t[FILETYPE] -o [NAME].svg [NAME].dot
        shell_exec('dot -T' . self::GRAPH_FILETYPE . ' -o resources/svg/' . $name
                    . self::GRAPH_FILETYPEb . ' resources/dot/' . $name . '.dot');

        return 'resources/svg/' . $name . self::GRAPH_FILETYPE;
    }

    protected function deployGraph($graph)
    {

    }
}