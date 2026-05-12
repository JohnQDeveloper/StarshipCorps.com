#!/bin/bash
source env.sh

echo "REMOVING EXSISTING CONTAINERS..." && \
docker stop valkey && docker rm -f valkey 2>/dev/null || true && \
docker stop starshipcorps-web && docker rm -f starshipcorps-web 2>/dev/null || true && \
docker stop starshipcorps-cron && docker rm -f starshipcorps-cron 2>/dev/null || true && \
echo "REMOVED EXISTING CONTAINERS"

echo "CREATING DOCKER NETWORK..." && \
docker network create starshipcorps-network 2>/dev/null || true && \
echo "NETWORK CREATED"

echo "BUILDING CONTAINERS..." && \
echo "BUILDING WEB CONTAINER..." && \
docker build -t starshipcorps-web:local -f Dockerfile.web.dev .  && \
echo "WEB CONTAINER BUILT"  && \
echo "BUILDING CRON CONTAINER" && \
docker build -t starshipcorps-cron:local -f Dockerfile.cron.dev .  && \
echo "CRON CONTAINER BUILT"  && \

echo "LAUNCHING CONTAINERS..." && \
echo "RUNNING VALKEY"  && \

docker run --rm -d -p 6379:6379 --network starshipcorps-network --name valkey valkey/valkey:8.1.3  && \
echo "VALKEY IS RUNNING" && \
echo "RUNNING WEB CONTAINER"  && \
docker run --rm -d -v $PWD:/app -p 80:80 -p 443:443 --network starshipcorps-network --tty -it \
--env DB_USER=$DB_USER \
--env DB_PASSWORD=$DB_PASSWORD \
--env DB_HOST=$DB_HOST \
--env RESEND_API_KEY=$RESEND_API_KEY \
--env REDIS_HOST=valkey \
--env REDIS_PORT=6379 \
--env DEBUG=true \
--env ENVIRONMENT=Dev \
--env HOSTNAME=localhost \
--name starshipcorps-web starshipcorps-web:local && \
echo "WEB CONTAINER IS RUNNING" && \
echo "RUNNING CRON CONTAINER"  && \
docker run --rm -d -v $PWD:/app --network starshipcorps-network --tty -it \
--env DB_USER=$DB_USER \
--env DB_PASSWORD=$DB_PASSWORD \
--env DB_HOST=$DB_HOST \
--env RESEND_API_KEY=$RESEND_API_KEY \
--env REDIS_HOST=valkey \
--env REDIS_PORT=6379 \
--env DEBUG=true \
--env ENVIRONMENT=Dev \
--env HOSTNAME=localhost \
--name starshipcorps-cron starshipcorps-cron:local && \
echo "CRON CONTAINER IS RUNNING" && \
echo "CONTAINERS LAUNCHED SUCCESSFULLY" && \
echo "ACCESS THE WEB APP AT http://localhost" && \
echo "TO VIEW LOGS, USE 'docker logs <container_name>'" && \
echo "TO RESTART CONTAINERS, RE-RUN THIS SCRIPT "
