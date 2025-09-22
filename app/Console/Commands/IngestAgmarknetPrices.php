<?php

namespace App\Console\Commands;

use App\Services\AgmarknetIngestService;
use Illuminate\Console\Command;

class IngestAgmarknetPrices extends Command
{
	protected $signature = 'prices:ingest-agmarknet {--date=} {--limit=5000}';
	protected $description = 'Fetch daily mandi prices from data.gov.in and upsert into prices table';

	public function handle(AgmarknetIngestService $service): int
	{
		$apiKey = config('services.data_gov.api_key') ?? env('DATA_GOV_API_KEY');
		if (!$apiKey) {
			$this->error('DATA_GOV_API_KEY not set in environment.');
			return self::FAILURE;
		}

		$date = $this->option('date') ?: null; // YYYY-MM-DD or null for all
		$limit = (int) $this->option('limit');
		$result = $service->ingest($apiKey, $date, $limit);
		$this->info('Upserted: '.$result['upserted'].' rows in '.$result['duration_ms'].'ms');
		return self::SUCCESS;
	}
}

