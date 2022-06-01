#!/bin/bash

curl -X POST --data-binary "query='SELECT DISTINCT ?label WHERE { ?s a ?label . }'"  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > neptune.rdf
