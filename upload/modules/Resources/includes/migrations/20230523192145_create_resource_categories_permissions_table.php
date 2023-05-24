<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResourceCategoriesPermissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_categories_permissions');

        $table
            ->addColumn('category_id', 'integer', ['length' => 11])
            ->addColumn('group_id', 'integer', ['length' => 11])
            ->addColumn('view', 'boolean', ['default' => true])
            ->addColumn('post', 'boolean', ['default' => false])
            ->addColumn('move_resource', 'boolean', ['default' => false])
            ->addColumn('edit_resource', 'boolean', ['default' => false])
            ->addColumn('delete_resource', 'boolean', ['default' => false])
            ->addColumn('edit_review', 'boolean', ['default' => false])
            ->addColumn('delete_review', 'boolean', ['default' => false])
            ->addColumn('download', 'boolean', ['default' => false])
            ->addColumn('premium', 'boolean', ['default' => false]);

        $table->create();
    }
}
