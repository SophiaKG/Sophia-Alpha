#!/bin/bash

curl -X POST \
    -H 'Content-Type: application/json' \ 
    https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/loader -d '
    {
      "source" : "s3://drupal-sophia-store/Migration/20200505/SillyWalks.ttl",
      "format" : "turtle",
      "iamRoleArn" : "arn:aws:iam::075128907886:role/NeptuneLoadFromS3",
      "region" : "ap-southeast-2",
      "failOnError" : "FALSE",
      "parallelism" : "MEDIUM",
      "updateSingleCardinalityProperties" : "FALSE",
      "queueRequest" : "TRUE"
    }'

#curl -G 'https://sophia-neptune.cbkhatvpiwzj.ap-southeast-2.neptune.amazonaws.com:8182/loader/$LOAD_ID'
#https://docs.aws.amazon.com/neptune/latest/userguide/bulk-load-data.html
