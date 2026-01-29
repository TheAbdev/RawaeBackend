<?php

namespace App\Console\Commands;

use App\Models\Mosque;
use App\Services\MosqueNeedScoreService;
use Illuminate\Console\Command;

class RecalculateMosqueNeedScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mosques:recalculate-need-scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate need scores for all active mosques';

    /**
     * Execute the console command.
     */
    public function handle(MosqueNeedScoreService $needScoreService): int
    {
        $this->info('Recalculating need scores for all mosques...');

        $mosques = Mosque::where('is_active', true)->get();
        $bar = $this->output->createProgressBar($mosques->count());
        $bar->start();

        foreach ($mosques as $mosque) {
            $needScoreService->updateNeedLevel($mosque);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Need scores recalculated successfully!');

        return Command::SUCCESS;
    }
}

