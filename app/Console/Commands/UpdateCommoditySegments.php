<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Commodity;

class UpdateCommoditySegments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commodities:update-segments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update commodity segments based on their names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting commodity segment update...');
        
        $commodities = Commodity::all();
        $updated = 0;
        $segmentCounts = [
            'grains' => 0,
            'vegetables' => 0,
            'fruits' => 0,
            'pulses' => 0,
            'oils' => 0,
            'spices' => 0,
            'dry-fruits' => 0,
            'rice' => 0
        ];

        $progressBar = $this->output->createProgressBar($commodities->count());
        $progressBar->start();

        foreach ($commodities as $commodity) {
            $name = strtolower($commodity->name);
            $segment = $this->mapCommodityToSegment($name);
            
            if ($commodity->segment !== $segment) {
                $commodity->update(['segment' => $segment]);
                $updated++;
            }
            
            $segmentCounts[$segment]++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Updated {$updated} commodities with proper segments");
        $this->newLine();
        
        $this->info('Segment distribution:');
        foreach ($segmentCounts as $segment => $count) {
            $this->line("  {$segment}: {$count} commodities");
        }

        return Command::SUCCESS;
    }

    private function mapCommodityToSegment(string $name): string
    {
        $grains = ['wheat','barley','oats','millet','bajra','jowar','maize','corn','paddy'];
        $vegetables = ['onion','tomato','potato','brinjal','cabbage','cauliflower','carrot','radish','beetroot','cucumber','pumpkin','gourd','ladyfinger','okra','spinach','coriander','mint','lettuce','capsicum','bell pepper','chilli','green chilli','red chilli'];
        $fruits = ['apple','amla','amaranthus','mango','banana','orange','lemon','lime','grapes','pomegranate','papaya','guava','watermelon','muskmelon','coconut','pineapple','strawberry','cherry','peach','pear','plum','kiwi','avocado','fig','date','jamun','ber','custard apple','sapota','chikoo'];
        $pulses = ['chana','gram','arhar','toor','tur','moong','masoor','urad','lentil','pea','peas','dal','dal','alasande','cowpea','black','green','red','yellow'];
        $oils = ['mustard','sarson','groundnut','peanut','soybean','sunflower','sesame','til','palm','linseed','oil','coconut','castor','neem'];
        $spices = ['jeera','cumin','dhaniya','coriander','haldi','turmeric','chilli','red chilli','mirch','cardamom','elaichi','clove','laung','fennel','saunf','fenugreek','methi','ajwan','ajwain','pepper','kali','garlic','adrak','ginger'];
        $dry = ['almond','badam','cashew','kaju','raisins','kishmish','pista','walnut','akhrot','dates','khajur','dry','fruit','nuts','kismis','pistachio'];

        $map = [
            'grains' => $grains,
            'vegetables' => $vegetables,
            'fruits' => $fruits,
            'pulses' => $pulses,
            'oils' => $oils,
            'spices' => $spices,
            'dry-fruits' => $dry,
        ];

        foreach ($map as $segment => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return $segment;
                }
            }
        }

        // Rice varieties - check before other segments
        if (str_contains($name, 'rice') || str_contains($name, 'basmati') || str_contains($name, 'pusa') || str_contains($name, 'sarbati') || str_contains($name, 'non-basmati') || str_contains($name, 'paddy')) {
            return 'rice';
        }

        return 'grains';
    }
}
