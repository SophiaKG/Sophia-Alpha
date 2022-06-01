#!/bin/bash

QUERY="query=\
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	SELECT DISTINCT ?object\
	WHERE { ?subject rdfs:label ?object . }" 

QUERY1="query=\
	SELECT DISTINCT ?object\
	WHERE { ?subject ?predicate ?object . }" 

QUERY2="query=\
	PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>\
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	SELECT ?object\
	WHERE {?Entity a ns1:CommonwealthBody .\
	?Entity rdfs:label ?object .}"

QUERY3="query=\
	PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>\
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	SELECT ?object\
	WHERE {?Entity a ns1:Legislation .\
	?Entity rdfs:label ?object .}"


LABEL='"Ministry of Silly Walks"'
QUERY4="query=\
	PREFIX ns1: <file:///home/andnfitz/GovernmentEntities.owl#>\
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\
	CONSTRUCT { ?s ?p ${LABEL} .\
                ?s ?p1 ?label . }\
	WHERE { ?s ?p ${LABEL} .\
                ?s ?p1 ?label . }"

echo $QUERY4

curl -X POST --data-binary "$QUERY4" https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql > output/path.json
