<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Market;
use App\Models\State;
use Illuminate\Database\Seeder;

class BackfillHindiSeeder extends Seeder
{
	public function run(): void
	{
		// States: codes, type, Hindi names
		$states = [
			'rajasthan' => ['code' => 'RJ', 'type' => 'state', 'name_hi' => 'राजस्थान'],
			'punjab' => ['code' => 'PB', 'type' => 'state', 'name_hi' => 'पंजाब'],
			'haryana' => ['code' => 'HR', 'type' => 'state', 'name_hi' => 'हरियाणा'],
		];
		foreach ($states as $slug => $vals) {
			State::where('slug', $slug)->update($vals);
		}

		// Districts (selected common ones) with Hindi names
		$districts = [
			['state' => 'rajasthan', 'name' => 'Dausa', 'name_hi' => 'दौसा'],
			['state' => 'rajasthan', 'name' => 'Jaipur', 'name_hi' => 'जयपुर'],
			['state' => 'rajasthan', 'name' => 'Kota', 'name_hi' => 'कोटा'],
			['state' => 'punjab', 'name' => 'Ludhiana', 'name_hi' => 'लुधियाना'],
			['state' => 'haryana', 'name' => 'Karnal', 'name_hi' => 'करनाल'],
		];
		foreach ($districts as $d) {
			$state = State::where('slug', $d['state'])->first();
			if (!$state) continue;
			District::where('state_id', $state->id)->where('name', $d['name'])->update(['name_hi' => $d['name_hi']]);
		}

		// Markets (tehsils) with Hindi names
		$markets = [
			['state' => 'rajasthan', 'district' => 'Dausa', 'name' => 'Lalsot', 'name_hi' => 'लालसोट'],
			['state' => 'rajasthan', 'district' => 'Jaipur', 'name' => 'Chomu', 'name_hi' => 'चोमू'],
			['state' => 'haryana', 'district' => 'Karnal', 'name' => 'Karnal', 'name_hi' => 'करनाल'],
		];
		foreach ($markets as $m) {
			$state = State::where('slug', $m['state'])->first();
			if (!$state) continue;
			$district = District::where('state_id', $state->id)->where('name', $m['district'])->first();
			if (!$district) continue;
			Market::where('district_id', $district->id)->where('name', $m['name'])->update(['name_hi' => $m['name_hi']]);
		}

		$this->command->info('Backfilled Hindi labels and state codes/types for RJ/PB/HR.');
	}
}

