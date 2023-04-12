<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spipu\ProcessBundle\Tests\Functional;

use Spipu\CoreBundle\Tests\EntityManagerTestCaseTrait;
use Spipu\CoreBundle\Tests\WebTestCase;

abstract class AbstractFunctionalTest extends WebTestCase
{
    use EntityManagerTestCaseTrait;

    protected function resetDatabase(): void
    {
        $queries = [
            'delete from spipu_process_log',
            'delete from spipu_process_task',
        ];

        /** @var \PDO $pdo */
        $pdo = $this->getEntityManager()->getConnection()->getNativeConnection();
        foreach ($queries as $query) {
            $statement = $pdo->query($query);
            if ($statement === false) {
                throw new \Exception(implode(' - ', $pdo->errorInfo()));
            }
            $statement->execute();
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }
}
