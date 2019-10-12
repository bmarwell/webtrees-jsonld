FROM nginx:latest
COPY scripts /scripts
COPY nginx-webtrees.conf /etc/nginx/conf.d/default.conf
RUN apt-get update && apt-get -y install curl
