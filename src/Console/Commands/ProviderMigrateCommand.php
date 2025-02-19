<?php

namespace Luminee\Base\Console\Commands;

use Illuminate\Console\Command;

class ProviderMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luminee:provider:migrate {provider}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Module database';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $provider = $this->argument('provider');
        $this->call('migrate', ['--path' => "vendor/$provider/src/Database/migrations"]);
        $this->info("[$provider] Migrate Has Done! ^_^");
    }
    
}