<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceReleaseCreatorColumn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_releases');

        $table
            ->addColumn('creator_id', 'integer', ['length' => 11])
            ->addForeignKey('creator_id', 'nl2_users', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
