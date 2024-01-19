<?php

namespace App\Console\Commands;

use App\Lab\DataTable as LabDataTable;
use App\Lab\Nissan as LabNissan;
use Illuminate\Console\Command;

class DataTable extends Command
{
    protected $signature = 'lab:datatable';

    protected $description = 'Paginated! Searchable! Jump to Page-able!';

    public function handle()
    {
        $value = (new LabDataTable())->prompt();
    }
}
