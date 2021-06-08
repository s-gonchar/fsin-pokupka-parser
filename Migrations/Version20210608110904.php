<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210608110904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            create OR REPLACE view view_product as
            select
                   r.external_id as region_external_id, r.name as region_name,
                   a.external_id as agency_external_id, a.name as agency_name,
                   p.external_id, p.name, p.vendor, p.balance
            from products p
            left join agencies a on a.id = p.agency_id
            left join regions r on a.region_id = r.id
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop view view_product');
    }
}
