<?php

namespace Drupal\neptune_sync\Querier;

use Drupal\neptune_sync\Data\Model\Node;
use Drupal\neptune_sync\Utility\SophiaGlobal;
use Drupal\neptune_sync\Utility\Helper;
use Drupal\node\NodeInterface;

/**
 * Class QueryBuilder
 * @static
 * @package Drupal\neptune_sync\Querier
 * @author: Alexis Harper | DoF
 * This class is for utility function for when building queries
 */
class QueryBuilder {

    /**
     * Generate a usable neptune uri
     * As the field field_neptune_uri stores the uri based on the long form
     *  Uri that the node was imported from, we need to use strrchr to trim of the
     *  extended form uri and just get the local name before concatenating it to the user
     *  given namespace
     *
     * @param NodeInterface $node the node to get the uri for
     * @param String $prefix The prefix used to generate the uri (usually ns1|ns2)
     * @return string Neptune ready uri in the form of PREFIX:LocalName
     */
    public static function getUri(NodeInterface $node, String $prefix){
        return SophiaGlobal::IRI[$prefix]['prefix'] .
            substr(strrchr($node->get("field_neptune_uri")->getString(), "#"), 1);
    }
}
