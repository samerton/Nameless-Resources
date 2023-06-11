<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddResourcePriceDecimalColumn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources');

        $table
            ->addColumn('price_dec', 'decimal', ['default' => null, 'null' => true, 'precision' => 13, 'scale' => 4]);

        $table->update();
    }
}
