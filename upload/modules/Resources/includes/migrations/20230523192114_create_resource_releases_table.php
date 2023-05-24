<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateResourceReleasesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_releases');

        $table
            ->addColumn('resource_id', 'integer', ['length' => 11])
            ->addColumn('category_id', 'integer', ['length' => 11])
            ->addColumn('release_title', 'string', ['length' => 128])
            ->addColumn('release_description', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('release_tag', 'string', ['length' => 16])
            ->addColumn('created', 'integer', ['length' => 11])
            ->addColumn('downloads', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('rating', 'integer', ['default' => 0, 'length' => 11])
            ->addColumn('download_link', 'string', ['default' => null, 'length' => 255, 'null' => true]);

        $table->create();
    }
}
