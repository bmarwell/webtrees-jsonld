FROM debian:buster-slim

COPY loader.sh /loader.sh

ENV WT_VERSION=2.0.0-beta.4

RUN apt-get update && apt-get -y install curl unzip && chmod +x ./loader.sh

ENTRYPOINT ./loader.sh ${WT_VERSION}