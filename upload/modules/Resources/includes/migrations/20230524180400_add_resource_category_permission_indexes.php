<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceCategoryPermissionIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_categories_permissions');

        $table
            ->addForeignKey('category_id', 'nl2_resources_categories', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('group_id', 'nl2_groups', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
