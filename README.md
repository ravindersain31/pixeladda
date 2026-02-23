# YardSignPlus

---

## DISCLAIMER: Using Connections and Managers in Doctrine

The project uses two database connections: **default** and **replica**. These are configured in the `doctrine.yaml` file:

- **default**: The primary database connection, used for both read and write operations.
- **replica**: A read-only secondary replica, typically used for read operations to offload traffic from the primary database.

### Connection and Manager Usage

When interacting with the database, the corresponding manager should be accessed through the `ManagerRegistry` service. You can use the following approach to select the appropriate connection:

- To use the **default** connection (primary database):
  - `$this->managerRegistry->getManager(InstanceEnum::toString(InstanceEnum::DEFAULT));`
  
- To use the **replica** connection (if configured and enabled):
  - `$this->managerRegistry->getManager(InstanceEnum::toString(InstanceEnum::REPLICA));`

### Configuration Notes

- Ensure that your environment variables (`DATABASE_URL`, `DATABASE_REPLICA_*`) are properly set for both primary and replica databases.
- **Replica Usage**: For non-mutating queries (e.g., `SELECT`), make sure your application queries the replica database to reduce load on the primary database.

This setup helps maintain efficient database usage by separating read and write operations, optimizing performance, and reducing strain on the primary database.
# pixeladda
