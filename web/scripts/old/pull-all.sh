#!/bin/bash

curl -X POST --data-binary 'query=SELECT *'  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > all.rdf
