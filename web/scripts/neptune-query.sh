#!/bin/bash

curl -X POST --data-binary 'query=$1'  https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/sparql
