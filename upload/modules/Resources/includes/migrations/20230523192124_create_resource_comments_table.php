<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateResourceCommentsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_comments');

        $table
            ->addColumn('resource_id', 'integer', ['length' => 11])
            ->addColumn('author_id', 'integer', ['length' => 11])
            ->addColumn('content', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('release_tag', 'string', ['length' => 16])
            ->addColumn('created', 'integer', ['length' => 11])
            ->addColumn('reply_id', 'integer', ['default' => null, 'length' => 11, 'null' => true])
            ->addColumn('rating', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('hidden', 'boolean', ['default' => false]);

        $table->create();
    }
}
