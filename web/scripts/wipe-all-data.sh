#!/bin/bash

QUERY="update=\
       DELETE {?s ?p ?o} WHERE {?s ?p ?o.}"

echo $QUERY
curl -X POST --data-binary "$QUERY"  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql
