<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResourceCategoriesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_categories');

        $table
            ->addColumn('name', 'string', ['length' => 32])
            ->addColumn('description', 'text')
            ->addColumn('display_order', 'integer', ['default' => 0, 'length' => 11]);

        $table->create();
    }
}
