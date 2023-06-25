FROM nginx:1.21-alpine

COPY .docker/nginx/nginx.conf /etc/nginx
COPY .docker/nginx/default.conf.template /etc/nginx/templates/
RUN echo "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf.d

WORKDIR /var/www/drinkarena

COPY . .

EXPOSE 80
EXPOSE 443