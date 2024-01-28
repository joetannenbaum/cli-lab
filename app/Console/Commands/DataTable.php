<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\DataTable as LabDataTable;
use Illuminate\Console\Command;

class DataTable extends Command implements LabCommand
{
    public $displayName = 'Data Table';

    protected $signature = 'lab:datatable';

    protected $description = 'Paginated! Searchable! Jump to Page-able!';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab(): void
    {
        $value = (new LabDataTable())->prompt();
    }
}
