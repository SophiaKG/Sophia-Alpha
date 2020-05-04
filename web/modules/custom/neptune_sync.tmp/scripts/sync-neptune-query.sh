#!/bin/bash

curl -X POST --data-binary "query=SELECT DISTINCT ?subject ?predicate ?object WHERE { ?subject ?predicate ?object . }"  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > sync.json
