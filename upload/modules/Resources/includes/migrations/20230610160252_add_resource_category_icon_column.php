<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceCategoryIconColumn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_categories');

        $table
            ->addColumn('default_resource_icon', 'string', ['default' => null, 'length' => 512, 'null' => true]);

        $table->update();
    }
}
