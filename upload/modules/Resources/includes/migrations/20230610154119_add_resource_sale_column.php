<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourceSaleColumn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources');

        $table
            ->addColumn('sale', 'integer', ['default' => null, 'length' => 11, 'null' => true]);

        $table->update();
    }
}
