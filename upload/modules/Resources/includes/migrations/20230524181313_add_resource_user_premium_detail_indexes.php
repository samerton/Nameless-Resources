<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceUserPremiumDetailIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_users_premium_details');

        $table
            ->addForeignKey('user_id', 'nl2_users', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
