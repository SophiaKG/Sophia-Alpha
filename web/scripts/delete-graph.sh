#!/bin/bash
curl https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql -d "update=CLEAR GRAPH <http://aws.amazon.com/neptune/vocab/v001>" 
