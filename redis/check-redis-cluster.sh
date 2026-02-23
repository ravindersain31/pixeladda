#!/bin/bash
PORTS=(7001 7002 7003 7004 7005 7006)

for PORT in "${PORTS[@]}"; do
  echo "🔍 Node on port $PORT:"
  redis-cli -p $PORT cluster info | grep cluster_state
done

echo "📌 Full cluster nodes:"
redis-cli -p 7001 cluster nodes
