<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourcePaymentIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_payments');

        $table
            ->addForeignKey('user_id', 'nl2_users', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('resource_id', 'nl2_resources', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
