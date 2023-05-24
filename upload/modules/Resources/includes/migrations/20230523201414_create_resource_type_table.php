<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResourceTypeTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources_types');
        
        $table
            ->addColumn('label', 'string', ['length' => 12]);

        $table->create();
    }
}
