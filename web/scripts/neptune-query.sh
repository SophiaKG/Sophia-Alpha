#!/bin/bash

QUERY="query=\
        PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>\
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#i>\
        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>\
        PREFIX owl: <http://www.w3.org/2002/07/owl#>\
        PREFIX ns2: <file:///C:/SophiaBuild/data/OntologyFiles/GovernmentEntities.owl#>\
        ${1}"

echo $QUERY
curl -X POST --data-binary "$QUERY"  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql
