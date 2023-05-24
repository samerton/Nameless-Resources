<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResourceUsersPremiumDetailsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_users_premium_details');

        $table
            ->addColumn('user_id', 'integer', ['length' => 11])
            ->addColumn('paypal_email', 'string', ['default' => null, 'length' => 256, 'null' => true]);

        $table->create();
    }
}
