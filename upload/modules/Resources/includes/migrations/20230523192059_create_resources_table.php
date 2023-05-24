<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateResourcesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources');

        $table
            ->addColumn('category_id', 'integer', ['length' => 11])
            ->addColumn('creator_id', 'integer', ['length' => 11])
            ->addColumn('name', 'string', ['length' => 64])
            ->addColumn('short_description', 'string', ['default' => null, 'length' => 64, 'null' => true])
            ->addColumn('has_icon', 'boolean', ['default' => false])
            ->addColumn('icon', 'string', ['default' => null, 'length' => 512, 'null' => true])
            ->addColumn('icon_updated', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('description', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('contributors', 'text')
            ->addColumn('views', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('downloads', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('created', 'integer', ['length' => 11])
            ->addColumn('updated', 'integer', ['length' => 11])
            ->addColumn('github_url', 'string', ['default' => null, 'length' => 128, 'null' => true])
            ->addColumn('github_username', 'string', ['default' => null, 'length' => 64, 'null' => true])
            ->addColumn('github_repo_name', 'string', ['default' => null, 'length' => 64, 'null' => true])
            ->addColumn('rating', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('latest_version', 'string', ['default' => null, 'length' => 32, 'null' => true])
            ->addColumn('type', 'integer', ['default' => 0, 'length' => 1])
            ->addColumn('price', 'string', ['default' => null, 'length' => 16, 'null' => true])
            ->addColumn('payment_email', 'string', ['default' => null, 'length' => 256, 'null' => true]);

        // todo - add new table for github details (plus user integrations?)

        $table->create();
    }
}
