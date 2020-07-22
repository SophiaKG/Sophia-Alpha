#!/bin/bash

QUERY+='{
"source" : "s3://drupal-sophia-store/'
#Migration/20200505/SillyWalks.ttl",
QUERY+=${1} #filename
QUERY+='", '
QUERY+='"format" : "'
#'turtle",
QUERY+=${2} #file type
QUERY+='", "iamRoleArn" : "arn:aws:iam::075128907886:role/NeptuneLoadFromS3",
      "region" : "ap-southeast-2",
      "failOnError" : "FALSE",
      "parallelism" : "MEDIUM",
      "updateSingleCardinalityProperties" : "FALSE",
      "queueRequest" : "TRUE",
      "parserConfiguration" : {
        "namedGraphUri" : "http://aws.amazon.com/neptune/vocab/v00'
QUERY+=${3} #single digit graph no
QUERY+='"
      }
    }'

echo "Executing: "
echo $QUERY

curl -X POST -H 'Content-Type: application/json' https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/loader -d "$QUERY"

echo "Run the cmd below to check status"
echo "curl -G 'https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/loader/"

#https://docs.aws.amazon.com/neptune/latest/userguide/bulk-load-data.html
