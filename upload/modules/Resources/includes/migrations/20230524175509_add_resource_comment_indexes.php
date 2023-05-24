<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceCommentIndexes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_comments');

        $table
            ->addForeignKey('resource_id', 'nl2_resources', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('author_id', 'nl2_users', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('reply_id', 'nl2_resources_comments', 'id', ['delete'=> 'CASCADE', 'update' => 'CASCADE']);

        $table->update();
    }
}
