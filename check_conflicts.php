<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$total   = DB::table('conflicts')->count();
$validTT = DB::table('conflicts')->join('timetables', 'conflicts.timetable_id', '=', 'timetables.id')->count();
$validS1 = DB::table('conflicts')->join('timetable_slots', 'conflicts.slot_id_1', '=', 'timetable_slots.id')->count();
$col     = DB::select('SHOW COLUMNS FROM conflicts WHERE Field = "conflict_type"')[0];
$columns = implode(', ', array_column(DB::select('SHOW COLUMNS FROM conflicts'), 'Field'));
$types   = DB::table('conflicts')->selectRaw('conflict_type, count(*) as cnt')->groupBy('conflict_type')->get();

echo "=== conflicts table ===" . PHP_EOL;
echo "Columns:          $columns" . PHP_EOL;
echo "Enum:             {$col->Type}" . PHP_EOL;
echo "Total rows:       $total" . PHP_EOL;
echo "Valid timetable:  $validTT" . PHP_EOL;
echo "Valid slot_id_1:  $validS1" . PHP_EOL;
echo "Breakdown:" . PHP_EOL;
foreach ($types as $t) echo "  {$t->conflict_type}: {$t->cnt}" . PHP_EOL;
if ($total === 0) echo "  (no rows)" . PHP_EOL;
