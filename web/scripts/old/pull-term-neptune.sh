#!/bin/bash

curl -X POST --data-binary 'query=
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SELECT ?disc ?label
{
  $1 $2 ?disc .
  ?disc rdfs:label ?label
}'  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > queryres.rdf
