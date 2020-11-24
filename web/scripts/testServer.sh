#!/bin/bash

curl -X POST --data-binary "query= SELECT DISTINCT * FROM <http://aws.amazon.com/neptune/vocab/v001> WHERE { ?o ?p ?s. } Limit 100" https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql
