<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Market;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RegionsSeeder extends Seeder
{
	public function run(): void
	{
		$path = 'regions/india_regions.json';
		if (!Storage::disk('local')->exists($path)) {
			$this->command->warn("Missing file: storage/app/{$path}. Creating with sample data...");
			Storage::disk('local')->put($path, json_encode($this->sampleData(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
		}

		$json = Storage::disk('local')->get($path);
		$data = json_decode($json, true);
		if (!is_array($data)) {
			$this->command->error('Invalid regions JSON.');
			return;
		}

		foreach ($data as $stateRow) {
			$state = State::updateOrCreate(
				['slug' => $stateRow['slug']],
				[
					'name' => $stateRow['name'],
					'code' => $stateRow['code'] ?? null,
					'country' => 'India',
				]
			);

			$districts = $stateRow['districts'] ?? [];
			foreach ($districts as $districtRow) {
				$districtName = is_array($districtRow) ? ($districtRow['name'] ?? '') : (string) $districtRow;
				if ($districtName === '') {
					continue;
				}
				$district = District::updateOrCreate(
					[
						'state_id' => $state->id,
						'slug' => str($state->slug . '-' . $districtName)->slug(),
					],
					[
						'name' => $districtName,
					]
				);

				$tehsils = is_array($districtRow) ? ($districtRow['tehsils'] ?? []) : [];
				if (!empty($tehsils)) {
					$marketRows = [];
					foreach ($tehsils as $tehsilName) {
						$marketRows[] = [
							'state_id' => $state->id,
							'district_id' => $district->id,
							'name' => $tehsilName,
							'slug' => str($state->slug . '-' . $district->slug . '-' . $tehsilName)->slug(),
							'type' => 'tehsil',
							'created_at' => now(),
							'updated_at' => now(),
						];
					}
					Market::upsert($marketRows, ['district_id','slug'], ['name','updated_at','type']);
				}
			}
		}

		$this->command->info('Regions seeded.');
	}

	private function sampleData(): array
	{
		return [
			[
				'name' => 'Rajasthan',
				'slug' => 'rajasthan',
				'code' => 'RJ',
				'districts' => [
					['name' => 'Jaipur', 'tehsils' => ['Jaipur','Chomu','Phagi','Sanganer','Amber','Bassi']],
					['name' => 'Bikaner', 'tehsils' => ['Bikaner','Nokha','Lunkaransar','Khajuwala']],
					['name' => 'Sikar', 'tehsils' => ['Sikar','Fatehpur','Lachhmangarh','Neem Ka Thana']],
					['name' => 'Jodhpur', 'tehsils' => ['Jodhpur','Luni','Bilara','Osian']],
					['name' => 'Kota', 'tehsils' => ['Kota','Ladpura','Sangod','Pipalda']],
				]
			],
			[
				'name' => 'Punjab',
				'slug' => 'punjab',
				'code' => 'PB',
				'districts' => [
					['name' => 'Ludhiana', 'tehsils' => ['Ludhiana','Khanna','Samrala','Raikot']],
					['name' => 'Amritsar', 'tehsils' => ['Amritsar','Ajnala','Tarn Taran']],
					['name' => 'Moga', 'tehsils' => ['Moga','Baghapurana','Nihal Singh Wala']],
					['name' => 'Bathinda', 'tehsils' => ['Bathinda','Rampura Phul','Talwandi Sabo']],
					['name' => 'Ferozepur', 'tehsils' => ['Ferozepur','Zira','Guru Har Sahai']],
				]
			],
			[
				'name' => 'Haryana',
				'slug' => 'haryana',
				'code' => 'HR',
				'districts' => [
					['name' => 'Karnal', 'tehsils' => ['Karnal','Gharaunda','Assandh','Nilokheri']],
					['name' => 'Kurukshetra', 'tehsils' => ['Kurukshetra','Shahbad','Pehowa']],
					['name' => 'Hisar', 'tehsils' => ['Hisar','Hansi','Barwala']],
					['name' => 'Sirsa', 'tehsils' => ['Sirsa','Ellenabad','Rania']],
					['name' => 'Fatehabad', 'tehsils' => ['Fatehabad','Tohana','Ratia']],
				]
			],
		];
	}
}

