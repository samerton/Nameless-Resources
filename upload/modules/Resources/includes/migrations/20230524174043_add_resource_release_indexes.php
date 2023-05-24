<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceReleaseIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_releases');

        $table
            ->addForeignKey('category_id', 'nl2_resources_categories', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('resource_id', 'nl2_resources', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
