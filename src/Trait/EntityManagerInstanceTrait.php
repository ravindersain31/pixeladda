<?php

namespace App\Trait;

use App\Attribute\ReadOnlyAttribute;
use App\Enum\DBInstanceEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * IMPORTANT USAGE NOTE:
 * ======================
 * This trait facilitates dynamic selection of Doctrine EntityManager instances
 * using the DBInstanceEnum (e.g., for master/slave or read/write separation).
 *
 * ⚠️ CRITICAL RULE – DO NOT MIX USAGE:
 * -------------------------------------
 * ❌ DO NOT use the read-only Reader (or read-only EntityManager) at the same time
 *     as a writable EntityManagerInterface in the same transactional scope or
 *     service method. This can lead to:
 *       - Inconsistent data states
 *       - Stale reads
 *       - Unexpected Doctrine behavior (e.g. caching issues, flushed writes not visible)
 *
 * ✔️ Always separate read and write contexts clearly.
 *    For read-only methods, use a properly configured readonly DB instance.
 *    For write operations, always use the default or master instance.
 *
 * GENERAL NOTES:
 * ---------------
 * 1. `setManagerRegistry()` MUST be called before using this trait’s methods.
 * 2. `createQueryBuilder()` overrides default behavior for non-default DB instances.
 *    Make sure the consuming class provides `getClassName()`.
 * 3. `isReadOnly()` detects methods marked with the `ReadOnlyAttribute` via PHP 8 Attributes.
 *
 * ✅ RECOMMENDED:
 * ----------------
 * - Use this trait in services or repositories that require database routing logic.
 * - Be strict about context: either you're in a "read-mode" or "write-mode", not both.
 */

trait EntityManagerInstanceTrait
{
    private ManagerRegistry $managerRegistry;
    protected array $methodAttributesCache = [];

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getEM(DBInstanceEnum $instance = DBInstanceEnum::DEFAULT): EntityManagerInterface
    {
        return $this->managerRegistry->getManager($instance->value);
    }

    /**
     * NOTE: This method is overridden to support non-default DB instances.
     * Usage: $this->createQueryBuilder($alias, $indexBy, DBInstanceEnum::READONLY);
     */
    public function createQueryBuilder(?string $alias = null, ?string $indexBy = null, DBInstanceEnum $dbInstance = DBInstanceEnum::DEFAULT): QueryBuilder
    {
        if ($dbInstance === DBInstanceEnum::DEFAULT) {
            return parent::createQueryBuilder($alias, $indexBy);
        }

        return $this->getEM($dbInstance)->createQueryBuilder()->select($alias)->from($this->getClassName(), $alias, $indexBy);
    }

    public function isReadOnly(string $methodName): bool
    {
        if (!isset($this->methodAttributesCache[$methodName])) {
            $reflection = new \ReflectionClass($this);
            $method = $reflection->getMethod($methodName);
            $this->methodAttributesCache[$methodName] = count($method->getAttributes(ReadOnlyAttribute::class)) > 0;
        }
        return $this->methodAttributesCache[$methodName];
    }
}
