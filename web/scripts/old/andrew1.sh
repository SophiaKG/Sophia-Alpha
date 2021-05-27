#!/bin/bash

curl -X POST --data-binary 'query=SELECT ?object ?predicate WHERE {<subject.iri> ?predicate ?object}'  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > drew1.rdf
