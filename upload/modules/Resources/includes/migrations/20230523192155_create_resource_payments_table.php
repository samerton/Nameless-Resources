<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResourcePaymentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_payments');

        $table
            ->addColumn('user_id', 'integer', ['length' => 11])
            ->addColumn('resource_id', 'integer', ['length' => 11])
            ->addColumn('transaction_id', 'string', ['length' => 32])
            ->addColumn('created', 'integer', ['length' => 11])
            ->addColumn('status', 'integer', ['default' => 0, 'length' => 1]);

        $table->create();
    }
}
