<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateResourceTypeColumn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('nl2_resources');

        $table->changeColumn('type', 'string', ['default' => 'free', 'length' => 12]);

        $table->update();

        if (
            $this->isMigratingUp() &&
            ($stmt = $this->query('SELECT `id`, `type` FROM nl2_resources')) &&
            $data = $stmt->fetchAll()
        ) {
            foreach ($data as $resource) {
                $type = $resource['type'] === 1 ? 'premium' : 'free';
                $this->execute('UPDATE nl2_resources SET `type` = ? WHERE `id` = ?', [$type, $resource['id']]);
            }
        }
    }
}
