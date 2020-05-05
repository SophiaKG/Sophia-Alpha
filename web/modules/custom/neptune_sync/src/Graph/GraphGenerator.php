<?php

namespace Drupal\neptune_sync\Graph;


use Drupal\neptune_sync\querier\QueryManager;

class GraphGenerator
{
    const GRAPH_CONVERTER_PATH = '';
    const GRAPH_FILETYPE = 'svg';
    const MODULE_RESOURCE_DIR = 'modules/custom/neptune_sync/resources/';
    const GRAPH_VISUALIZER_PATH = '/home/ec2-user/workspace/ontology-visualization/' .
                                    'ontology_viz.py';

    protected $working_dir = 'graphs';

    public function generateGraph($query)
    {
        /* query generatioon hardcoded for showcase
        //create a filename
        $name = bin2hex(mcrypt_create_iv(15, MCRYPT_DEV_URANDOM));

        $query->setOutputPath('resources/ttl/' . $name . '.ttl');
        $query_mgr = new QueryManager();
        $query_mgr->runCustomQuery($query);
        */

        $name = 'demo';

        //arg: ontology_viz.py -o [OUTPUT] [INPUT] -O [ONTOLOGY|OPTIONAL]
        //arg: ontology_viz.py -o [NAME].dot [name].ttl
        $cmd = 'python3 ' . self::GRAPH_VISUALIZER_PATH . ' -o ' . self::MODULE_RESOURCE_DIR
                . 'dot/' . $name . '.dot ' . self::MODULE_RESOURCE_DIR . 'ttl/'. $name . '.ttl';
        $res = shell_exec($cmd);

        //log
        \drupal::logger('neptune_sync') ->notice('Graph ' . $name .
            ' created. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);

        //arg: dot -tsvg -o [OUTPUT] [INPUT]
        //arg: dot -t[FILETYPE] -o [NAME].svg [NAME].dot
        $cmd = 'dot -T' . self::GRAPH_FILETYPE . ' -o sites/default/files/graphs/'
                . $name . self::GRAPH_FILETYPE . ' ' . self::MODULE_RESOURCE_DIR . 'dot/' . $name . '.dot';
        $res = shell_exec($cmd);

        //log
        \drupal::logger('neptune_sync') ->notice('Graph ' . $name .
            ' converted to svg. Cmd: ' . $cmd . "\n\nExec result:\n" . $res);

        return '/sites/default/files/graphs/' . $name . '.' . self::GRAPH_FILETYPE;
    }

    protected function deployGraph($graph)
    {

    }
}