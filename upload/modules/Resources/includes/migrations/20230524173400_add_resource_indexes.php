<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources');

        $table
            ->addForeignKey('category_id', 'nl2_resources_categories', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('creator_id', 'nl2_users', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->save();
    }
}
