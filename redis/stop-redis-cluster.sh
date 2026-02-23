#!/bin/bash

BASE_DIR=~/redis-cluster
PORTS=(7001 7002 7003 7004 7005 7006)

echo "🛑 Stopping Redis cluster..."

for PORT in "${PORTS[@]}"; do
  redis-cli -p $PORT shutdown nosave 2>/dev/null
  echo "   ❌ Stopped Redis on port $PORT"
done

echo "🧹 Cleaning data..."
rm -rf $BASE_DIR

echo "✅ All nodes stopped and data cleaned."
