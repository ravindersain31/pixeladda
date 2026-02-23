#!/bin/bash

BASE_DIR=~/redis-cluster
PORTS=(7001 7002 7003 7004 7005 7006)

# Get the host IP that Docker can reach
HOST_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || echo "host.docker.internal")

mkdir -p $BASE_DIR

echo "🚀 Preparing Redis cluster in $BASE_DIR"
echo "📡 Using host IP: $HOST_IP"

for PORT in "${PORTS[@]}"; do
  DIR="$BASE_DIR/$PORT"
  mkdir -p $DIR

  # Cleanup old files if any
  rm -f $DIR/nodes.conf
  rm -f $DIR/appendonly.aof
  rm -f $DIR/dump.rdb
  rm -f $DIR/redis.log

  echo "   ⚡ Starting Redis on port $PORT"
  redis-server --port $PORT \
    --bind 0.0.0.0 \
    --cluster-enabled yes \
    --cluster-announce-ip $HOST_IP \
    --cluster-announce-port $PORT \
    --cluster-config-file $DIR/nodes.conf \
    --cluster-node-timeout 5000 \
    --appendonly yes \
    --daemonize yes \
    --logfile $DIR/redis.log \
    --dbfilename dump.rdb \
    --dir $DIR \
    --protected-mode no
done

sleep 2

echo "🔗 Checking if cluster already exists..."
CLUSTER_INFO=$(redis-cli -h $HOST_IP -p 7001 cluster info 2>/dev/null | grep cluster_state)

if [[ "$CLUSTER_INFO" == *"ok"* ]]; then
  echo "✅ Cluster already running, skipping cluster creation."
else
  echo "🔨 Creating new cluster..."
  yes yes | redis-cli --cluster create \
    $HOST_IP:7001 \
    $HOST_IP:7002 \
    $HOST_IP:7003 \
    $HOST_IP:7004 \
    $HOST_IP:7005 \
    $HOST_IP:7006 \
    --cluster-replicas 1
fi

echo ""
echo "🎉 Redis Cluster is ready!"
echo "👉 Connect with: redis-cli -c -p 7001"
echo ""
echo "📝 Update your .env with:"
echo "VALKEY_HOST=$HOST_IP"