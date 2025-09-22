<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\District;
use App\Models\Market;
use App\Models\Price;
use App\Models\State;
use Illuminate\Support\Facades\Http;

class AgmarknetIngestService
{
	public function ingest(string $apiKey, ?string $dateYmd = null, int $limit = 5000): array
	{
		$resourceId = '9ef84268-d588-465a-a308-a864a43d0070';
		$url = 'https://api.data.gov.in/resource/'.$resourceId;
		$offset = 0;
		$totalUpserted = 0;
		$st = microtime(true);

		do {
			$params = [
				'api-key' => $apiKey,
				'format' => 'json',
				'limit' => $limit,
				'offset' => $offset,
			];
			if ($dateYmd) {
				// API uses dd/mm/yyyy; convert
				$parts = explode('-', $dateYmd);
				if (count($parts) === 3) {
					$params['filters[arrival_date]'] = sprintf('%02d/%02d/%04d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
				}
			}

			$response = Http::timeout(20)->get($url, $params);
			$response->throw();
			$body = $response->json();
			$records = $body['records'] ?? [];
			$count = is_countable($records) ? count($records) : 0;

			foreach ($records as $rec) {
				$stateName = trim((string)($rec['state'] ?? ''));
				$districtName = trim((string)($rec['district'] ?? ''));
				$marketName = trim((string)($rec['market'] ?? ''));
				$commodityName = trim((string)($rec['commodity'] ?? ''));
				$variety = trim((string)($rec['variety'] ?? '')) ?: null;
				$grade = trim((string)($rec['grade'] ?? '')) ?: null;
				$rawDate = trim((string)($rec['arrival_date'] ?? ''));
				$date = $this->parseDdMmYyyyToYmd($rawDate) ?? now()->toDateString();
				$minPrice = (float)($rec['min_price'] ?? 0);
				$maxPrice = (float)($rec['max_price'] ?? 0);
				$modalPrice = (float)($rec['modal_price'] ?? 0);

				if ($stateName === '' || $districtName === '' || $marketName === '' || $commodityName === '') {
					continue;
				}

				$state = State::firstOrCreate(
					['slug' => str($stateName)->slug()],
					['name' => $stateName, 'code' => null, 'country' => 'India']
				);

				$district = District::firstOrCreate(
					['state_id' => $state->id, 'slug' => str($state->slug.'-'.$districtName)->slug()],
					['name' => $districtName]
				);

				$market = Market::firstOrCreate(
					['district_id' => $district->id, 'slug' => str($state->slug.'-'.$district->slug.'-'.$marketName)->slug()],
					['state_id' => $state->id, 'name' => $marketName, 'type' => 'mandi']
				);

				$commodity = Commodity::firstOrCreate(
					['slug' => str($commodityName)->slug()],
					['name' => $commodityName, 'segment' => $this->mapCommodityToSegment($commodityName), 'unit' => 'quintal']
				);

				Price::updateOrCreate(
					[
						// Match on the DB unique constraint
						'date' => $date,
						'commodity_id' => $commodity->id,
						'state_id' => $state->id,
						'district_id' => $district->id,
						'market_id' => $market->id,
					],
					[
						'variety' => $variety,
						'grade' => $grade,
						'currency' => 'INR',
						'source' => 'data.gov.in',
						'min_price' => $minPrice,
						'max_price' => $maxPrice,
						'modal_price' => $modalPrice,
					]
				);
				$totalUpserted++;
			}

			$offset += $limit;
		} while ($count === $limit);

		return [
			'upserted' => $totalUpserted,
			'duration_ms' => (int) ((microtime(true) - $st) * 1000),
		];
	}

	private function parseDdMmYyyyToYmd(?string $ddmmyyyy): ?string
	{
		if (!$ddmmyyyy) return null;
		$parts = explode('/', $ddmmyyyy);
		if (count($parts) !== 3) return null;
		return sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
	}

	private function mapCommodityToSegment(string $name): string
	{
		$name = mb_strtolower($name);
		$grains = ['wheat','barley','oats','millet','bajra','jowar','maize','corn','paddy','rice'];
		$pulses = ['chana','gram','arhar','toor','tur','moong','masoor','urad','lentil','pea','peas'];
		$oils = ['mustard','sarson','groundnut','peanut','soybean','sunflower','sesame','til','palm','linseed'];
		$spices = ['jeera','cumin','dhaniya','coriander','haldi','turmeric','chilli','red chilli','mirch','cardamom','elaichi','clove','laung','fennel','saunf','fenugreek','methi'];
		$dry = ['almond','badam','cashew','kaju','raisins','kishmish','pista','walnut','akhrot','dates','khajur'];

		$map = [
			'grains' => $grains,
			'pulses' => $pulses,
			'oils' => $oils,
			'spices' => $spices,
			'dry-fruits' => $dry,
		];
		foreach ($map as $segment => $list) {
			foreach ($list as $kw) {
				if (str_contains($name, $kw)) return $segment;
			}
		}
		// Keep rice explicit if present
		if (str_contains($name, 'basmati') || str_contains($name, 'pusa') || str_contains($name, 'sarbati')) {
			return 'rice';
		}
		return 'grains';
	}
}

