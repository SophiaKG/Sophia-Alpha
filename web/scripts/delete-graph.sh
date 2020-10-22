#!/bin/bash

QUERY+="update=CLEAR GRAPH <http://aws.amazon.com/neptune/vocab/v00"
QUERY+=${1}
QUERY+=">" 

echo "Executing: "
echo $QUERY
curl https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql -d "$QUERY" 
